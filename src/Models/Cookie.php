<?php

namespace Anshu8858\TrackerVisitor\Models;

use Illuminate\Cookie\CookieJar;
use Illuminate\Http\Request;

use PragmaRX\Support\Config;
use Ramsey\Uuid\Uuid as UUID;

class Cookie extends Base
{
    protected $table = 'avt_cookies';

    private $config;
    private $request;
    private $cookieJar;

    public function __construct($model, Config $config, Request $request, CookieJar $cookieJar)
    {
        $this->config = $config;
        $this->request = $request;
        $this->cookieJar = $cookieJar;

        parent::__construct($model);
    }

    public function getId()
    {
        if (! $this->config->get('store_cookie_tracker')) {
            return;
        }

        if (! $cookie = $this->request->cookie($this->config->get('tracker_cookie_name'))) {
            $cookie = UUID::uuid4()->toString();

            $this->cookieJar->queue($this->config->get('tracker_cookie_name'), $cookie, 0);
        }

        return $this->findOrCreate(['uuid' => $cookie]);
    }
}
