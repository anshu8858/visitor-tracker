<?php

namespace App\Models\Avt;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'avt_events';

    /**
     * @var EventLog
     */
    private $eventLogRepository;

    /**
     * @var SystemClass
     */
    private $systemClassRepository;

    /**
     * @var Log
     */
    private $logRepository;

    /**
     * @var \PragmaRX\Support\Config
     */
    private $config;

    /**
     * @var \PragmaRX\Tracker\Eventing\EventStorage
     */
    private $eventStorage;

    public function __construct(
        $model,
        EventStorage $eventStorage,
        EventLog $eventLogRepository,
        SystemClass $systemClassRepository,
        Log $logRepository,
        Config $config
    ) {
        parent::__construct($model);
        
        $this->eventStorage = $eventStorage;
        $this->eventLogRepository = $eventLogRepository;
        $this->systemClassRepository = $systemClassRepository;
        $this->logRepository = $logRepository;
        $this->config = $config;
    }

    public function logEvents()
    {
        if (! $this->logRepository->getCurrentLogId()) {
            return;
        }

        foreach ($this->eventStorage->popAll() as $event) {
            if ($this->isLoggableEvent($event)) {
                $this->logEvent($event);
            }
        }
    }

    private function isLoggableEvent($event)
    {
        $forbidden = $this->config->get('do_not_log_events');

        // Illuminate Query may cause infinite recursion
        $forbidden[] = 'illuminate.query';

        return
            $event['event'] != $this->getObject($event['object'])

            &&

            ! in_array_wildcard($event['event'], $forbidden)

            &&

            ! $this->config->get('log_only_events')
                || in_array($event['event'], $this->config->get('log_only_events'));
    }

    public function logEvent($event)
    {
        $event = $this->makeEventArray($event);

        $evenId = $this->getEventId($event);

        if ($evenId) {
            $objectName = $this->getObjectName($event);

            $classId = $this->getClassId($objectName);

            $this->eventLogRepository->create(
                [
                    'log_id' => $this->logRepository->getCurrentLogId(),
                    'event_id' => $evenId,
                    'class_id' => $classId,
                ]
            );
        }
    }

    private function getObject($object)
    {
        if (is_object($object)) {
            $object = get_class($object);
        } elseif (is_array($object)) {
            $object = serialize($object);
        }

        return $object;
    }

    public function getAll($minutes, $results)
    {
        return $this->getModel()->allInThePeriod($minutes, $results);
    }

    /**
     * Get the object name from an event.
     *
     * @param $event
     *
     * @return null|string
     */
    private function getObjectName($event)
    {
        return isset($event['object'])
            ? $this->getObject($event['object'])
            : null;
    }

    /**
     * Get the system class id by object name.
     *
     * @param null|string $objectName
     *
     * @return null
     */
    private function getClassId($objectName)
    {
        return $objectName
            ? $this->systemClassRepository->findOrCreate(
                ['name' => $objectName],
                ['name']
            )
            : null;
    }

    /**
     * Get the event id.
     *
     * @param $event
     *
     * @return null
     */
    private function getEventId($event)
    {
        return $event['event']
            ? $this->findOrCreate(
                ['name' => $event['event']],
                ['name']
            )
            : null;
    }

    private function makeEventArray($event)
    {
        if (is_string($event)) {
            $event = [
                'event' => $event,
                'object' => null,
            ];
        }

        return $event;
    }



    public function allInThePeriod($minutes, $result)
    {
        $query =
            $this
                ->select(
                    'tracker_events.id',
                    'tracker_events.name',
                    $this->getConnection()->raw('count('.$this->getEventLogTableName().'.id) as total')
                )
                ->from('tracker_events')
                ->period($minutes, 'tracker_events_log')
                ->join('tracker_events_log', 'tracker_events_log.event_id', '=', 'tracker_events.id')
                ->groupBy('tracker_events.id', 'tracker_events.name')
                ->orderBy('total', 'desc');

        if ($result) {
            return $query->get();
        }

        return $query;
    }

    private function getEventLogTableName()
    {
        return $this->getTablePrefix().'tracker_events_log';
    }

    /**
     * @return string
     */
    private function getTablePrefix()
    {
        return $this->getConnection()->getTablePrefix();
    }
}
