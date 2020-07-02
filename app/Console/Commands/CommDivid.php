<?php 
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;
use App\Models\CommunityDividend;

class CommDivid extends Command {

    protected $name = 'CommDivid';//命令名称

    protected $description = '清楚每天的释放总量'; // 命令描述，没什么用

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {	
		\Log::info('结算每月业绩开始');

		$list=CommunityDividend::get();

        foreach($list as $v){
          CommunityDividend::where('id',$v->id)->update(['last_month'=>$v->this_month,'this_month'=>0]);
        }
          	
		
		\Log::info('结算每月业绩结束');
    }
}