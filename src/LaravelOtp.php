<?php

namespace ModernMcGuire\LaravelOtp;

use Illuminate\Contracts\Auth\Authenticatable;
use ModernMcGuire\LaravelOtp\Drivers\DriverContract;
use ModernMcGuire\LaravelOtp\Drivers\EmailDriver;
use ModernMcGuire\LaravelOtp\Drivers\QrDriver;
use ModernMcGuire\LaravelOtp\Drivers\SmsDriver;

class LaravelOtp
{
    public function __construct($app)
    {
        $this->app = $app;
        $this->config = $app['config']['laravel-otp'];
    }

    /**
     * @param  string  $driver
     *
     * @throws Exception
     */
    public function driver($driver = null): DriverContract
    {
        $driver = $driver ?: $this->getDefaultDriver();

        $methodName = 'create'.ucfirst($driver).'Driver';

        if (! method_exists($this, $methodName)) {
            throw new \InvalidArgumentException("Driver [$driver] not supported.");
        }

        return $this->$methodName();
    }

    protected function getDefaultDriver(): string
    {
        return $this->config['default_driver'] ?? 'email';
    }

    protected function createEmailDriver(): DriverContract
    {
        return new EmailDriver();
    }

    protected function createSmsDriver(): DriverContract
    {
        return new SmsDriver();
    }

    protected function createQrDriver(): DriverContract
    {
        return new QrDriver();
    }

    public function generateAndSendOtp(Authenticatable $user)
    {
        $driver = $this->driver();

        $otp = $driver->generateOtp();
        $driver->sendOtp($user, $otp);

        // save to the user
        $user->otp_secret = encrypt($otp);
        $user->otp_secret_expires_at = now()->addMinutes(config('laravel-otp.otp_expiry'));
        $user->save();
    }
}
