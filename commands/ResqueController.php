<?php

namespace app\vendor\rolies106\yii2resque\commands;

use yii\console\Controller;
use Yii;

/**
 * Resque command for php message queueing.
 *
 * This is a console command for manage Resque workers
 *
 * @author Rolies106 <rolies106@gmail.com>
 * @version 0.1.0
 */
class ResqueController extends Controller
{
    public $defaultAction = 'index';

    public function actionIndex()
    {
        echo <<<EOD
This is the command for the yii-resque component. Usage:

    ./yiic resque <command>

Available commands are:

    start --queue=[queue_name | *] --interval=[int] --verbose=[0|1] --count=[int]
    startrecurring --queue=[queue_name | *] --interval=[int] --verbose=[0|1]
    stop --quit=[0|1]

EOD;
    }

    protected function runCommand($queue, $interval, $verbose, $count, $script)
    {
        $return = 1;
        $yiiPath = 'yiipath';//Yii::getAlias('system');
        $appPath = Yii::getAlias('@app');
        $resquePath = Yii::getAlias('@vendor') . '/rolies106/yii2resque';
        $redis = Yii::$app->get('redis', false);

        if (empty($redis)) {
            echo "\n";
            echo "resque component cannot be found in your configuration.\n";
            echo "please check your console.php configuration for resque components.\n\n";
            echo "ERROR.\n\n";
            return $return;            
        }

        $server = $redis->hostname ?: 'localhost';
        $port = $redis->port ?: 6379;
        $host = $server.':'.$port;
        $db = $redis->database ?: 0;
        $auth = $redis->password ?: '';
        $prefix = isset($redis->prefix) ? $redis->prefix : '';
        $includeFiles = isset($redis->includeFiles) ? $redis->includeFiles : null;

        if (is_array($includeFiles)) {
            $includeFiles = implode(',', $includeFiles);
        }

        $command = 'nohup sh -c "PREFIX='.$prefix.' QUEUE='.$queue.' COUNT='.$count.' REDIS_BACKEND='.$host.' REDIS_BACKEND_DB='.$db.' REDIS_AUTH='.$auth.' INTERVAL='.$interval.' VERBOSE='.$verbose.' INCLUDE_FILES='.$includeFiles.' YII_PATH='.$yiiPath.' APP_PATH='.$appPath.' php '.$resquePath.'/bin/'.$script.'" >> '.$appPath.'/runtime/yii_resque_log.log 2>&1 &';
die(var_dump($command));
        exec($command, $return);

        return $return;
    }

    public function actionStart($queue = '*', $interval = 5, $verbose = 1, $count = 5)
    {
        $this->runCommand($queue, $interval, $verbose, $count, 'resque');
    }

    public function actionStartrecurring($queue = '*', $interval = 5, $verbose = 1, $count = 1)
    {
        $this->runCommand($queue, $interval, $verbose, $count, 'resque-scheduler');
    }

    public function actionStop($quit = null)
    {
        $quit_string = $quit ? '-s QUIT': '-9';
        exec("ps uxe | grep '".escapeshellarg(Yii::app()->basePath)."' | grep 'resque' | grep -v grep | awk {'print $2'} | xargs kill $quit_string");
    }
}
