<?php

namespace App\Console\Commands;

use App\Models\ExTeam;
use App\Models\SymbolHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateWeekLine extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create_week_line';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->bigLine(SymbolHistory::TYPE_W);
    }

    public function bigLine(int $type)
    {
        $ex_teams = ExTeam::all();
        $s_type   = bcsub($type, 1, 0);

        $cb = Carbon::createFromDate(date('Y-m-d H:i'));
        $st = $cb->subDay(7)->toDateTimeString();
        $et = $cb->toDateTimeString();

        $created_at = $cb->toDateTimeString();

        foreach ($ex_teams as $ex_team) {

            $team_id    = $ex_team->id;

            $v = SymbolHistory::whereType($s_type)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->sum('v') ?: 0;

            if ($v) {
                $h = SymbolHistory::whereType($s_type)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->max('h');
                $l = SymbolHistory::whereType($s_type)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->min('l');
                $o = SymbolHistory::whereType($s_type)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->orderBy('id', 'asc')->first()->o ?: 0;
                $c = SymbolHistory::whereType($s_type)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->orderBy('id', 'desc')->first()->c ?: 0;
            } else {
                $sym = SymbolHistory::whereType($type)->first();
				
                $h = $sym->c;
                $l = $sym->c;
                $o = $sym->c;
                $c = $sym->c;
            }

            SymbolHistory::create(compact('created_at', 'team_id', 'h', 'l', 'o', 'c', 'v', 'type'));
        }
    }
}
