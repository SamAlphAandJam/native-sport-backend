<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
        \App\Console\Commands\ClearAdverts::class,
        \App\Console\Commands\TransferNews::class,
        \App\Console\Commands\Headlines::class,
        \App\Console\Commands\ChampionsLeague::class,
        \App\Console\Commands\FootballNews::class,
        \App\Console\Commands\TopStories::class,
        \App\Console\Commands\OldNews::class,
        \App\Console\Commands\GeneralNews::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //
        $schedule->command('run:ClearAdverts')->daily();
        $schedule->command('clear:OldNews')->daily();
        $schedule->command('clear:GeneralNews')->monthly();
        //Night
        $schedule->command('populate:Headlines')->dailyAt('00:30')->timezone('Africa/Lagos');
        $schedule->command('populate:TransferNews')->dailyAt('00:30')->timezone('Africa/Lagos');
        $schedule->command('populate:ChampionsLeague')->dailyAt('00:30')->timezone('Africa/Lagos');
        $schedule->command('populate:FootballNews')->dailyAt('00:30')->timezone('Africa/Lagos');
        $schedule->command('populate:TopStories')->dailyAt('00:30')->timezone('Africa/Lagos');

        //Day
        $schedule->command('populate:Headlines')->dailyAt('12:30')->timezone('Africa/Lagos');
        $schedule->command('populate:TransferNews')->dailyAt('12:30')->timezone('Africa/Lagos');
        $schedule->command('populate:ChampionsLeague')->dailyAt('12:30')->timezone('Africa/Lagos');
        $schedule->command('populate:FootballNews')->dailyAt('12:30')->timezone('Africa/Lagos');
        $schedule->command('populate:TopStories')->dailyAt('12:30')->timezone('Africa/Lagos');

        //test
        // $schedule->command('populate:Headlines')->everyMinute();
        // $schedule->command('populate:TransferNews')->everyMinute();
        // $schedule->command('populate:ChampionsLeague')->everyMinute();
        // $schedule->command('populate:FootballNews')->everyMinute();
        // $schedule->command('populate:TopStories')->everyMinute();

    }
}


