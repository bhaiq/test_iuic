<?php

namespace App\Console\Commands;

use App\Models\ExTeam;
use App\Models\SymbolHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CreateSymbolHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create_symbol_history';

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

        $this->minLine();
        (date('i') % 15 == 0) && $this->bigLine(SymbolHistory::TYPE_15);
        (date('i') == 0) && $this->bigLine(SymbolHistory::TYPE_1H);
        (date('i') == 0 && date('H') % 4 == 0) && $this->bigLine(SymbolHistory::TYPE_4H);
        (date('i') == 0 && date('H') == 0) && $this->bigLine(SymbolHistory::TYPE_1D);
    }

    public function minLine()
    {
        $ex_teams = ExTeam::all();
        foreach ($ex_teams as $ex_team) {

            $created_at = Carbon::createFromDate(date('Y-m-d H:i'))->toDateTimeString();

            $now = SymbolHistory::where(['type' => SymbolHistory::TYPE_1, 'created_at' => $created_at, 'team_id' => $ex_team->id])->first();

            if (!$now) {

                $model = SymbolHistory::where(['type' => SymbolHistory::TYPE_1, 'team_id' => $ex_team->id])->orderBy('id', 'desc')->first();

                if ($model) {
                    $data['team_id']    = $ex_team->id;
                    $data['c']          = $model->c;
                    $data['o']          = $model->c;
                    $data['h']          = $model->c;
                    $data['l']          = $model->c;
                    $data['v']          = 0;
                    $data['created_at'] = $created_at;
                    SymbolHistory::create($data);
                }

            }
        }
    }

    public function bigLine(int $type)
    {
        $ex_teams = ExTeam::all();
        $s_type   = bcsub($type, 1, 0);

        $cb = Carbon::createFromDate(date('Y-m-d H:i'));
        switch ($type) {
            case SymbolHistory::TYPE_15:
                $st = $cb->subMinute(15)->toDateTimeString();
                break;
            case SymbolHistory::TYPE_1H:
                $st = $cb->subMinute(60)->toDateTimeString();
                break;
            case SymbolHistory::TYPE_4H:
                $st = $cb->subHour(4)->toDateTimeString();
                break;
            case SymbolHistory::TYPE_1D:
                $st = $cb->subHour(24)->toDateTimeString();
                break;
        }

        $et = Carbon::createFromDate(date('Y-m-d H:i'))->toDateTimeString();

        if ($type == SymbolHistory::TYPE_1D) {
            $created_at = $st;
        } else {
            $created_at = $et;
        }

        foreach ($ex_teams as $ex_team) {

            $h = 0;
            $l = 0;
            $o = 0;
            $c = 0;

            $team_id = $ex_team->id;

            $sym = SymbolHistory::whereType($type)->whereTeamId($team_id)->orderBy('id', 'desc')->first();

            $s_sym = SymbolHistory::whereType($s_type)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->get();

            $v = $s_sym->sum('v') ?: 0;

            if ($v) {
                $h = $s_sym->max('h');
                $l = $s_sym->min('l');
                if ($sym->toArray()) {
                    $o = $sym->c;
                } else {
                    $o = $s_sym->sortBy('id')->first()->o ?: 0;
                }
                $c = $s_sym->sortByDesc('id')->first()->c ?: 0;
            } else {
                if ($sym->toArray()) {
                    $h = $sym->c;
                    $l = $sym->c;
                    $o = $sym->c;
                    $c = $sym->c;
                }
            }

            SymbolHistory::create(compact('created_at', 'team_id', 'h', 'l', 'o', 'c', 'v', 'type'));
        }
    }

    public function min15Line()
    {
        $ex_teams = ExTeam::all();
        $type     = SymbolHistory::TYPE_15;

        foreach ($ex_teams as $ex_team) {

            $cb = Carbon::createFromDate(date('Y-m-d H:i'));
            $st = $cb->subMinute(15)->toDateTimeString();
            $et = $cb->toDateTimeString();

            $created_at = $cb->toDateTimeString();
            $team_id    = $ex_team->id;

            $v = SymbolHistory::whereType(SymbolHistory::TYPE_1)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->sum('v') ?: 0;

            if ($v) {
                $h = SymbolHistory::whereType(SymbolHistory::TYPE_1)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->max('h');
                $l = SymbolHistory::whereType(SymbolHistory::TYPE_1)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->min('l');
                $o = SymbolHistory::whereType(SymbolHistory::TYPE_1)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->orderBy('id', 'asc')->first()->o ?: 0;
                $c = SymbolHistory::whereType(SymbolHistory::TYPE_1)->whereTeamId($team_id)->whereBetween('created_at', [$st, $et])->orderBy('id', 'desc')->first()->c ?: 0;
            } else {
                $sym = SymbolHistory::whereType($type)->first();
                $h   = $sym->c;
                $l   = $sym->c;
                $o   = $sym->c;
                $c   = $sym->c;
            }

            SymbolHistory::create(compact('created_at', 'team_id', 'h', 'l', 'o', 'c', 'v', 'type'));
        }
    }
}
