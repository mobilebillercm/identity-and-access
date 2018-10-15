<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ProcessUserChangePassword implements ShouldQueue
{


    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected  $exchangeName;
    protected $exchangetype;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($exchangeName, $exchangetype, $message)
    {
        $this->exchangeName = $exchangeName;
        $this->exchangetype = $exchangetype;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->publishMessageToQueue($this->exchangeName, $this->exchangetype ,$this->message);
    }

    public function publishMessageToQueue($exchangeName, $exchangetype, $message){


        $connection = null;


        try {


           /* $connection = new AMQPSSLConnection(
                env('RABBIT_MQ_HOST'),
                env('RABBIT_MQ_SSL_PORT'),
                env('RABBIT_MQ_USER'),
                env('RABBIT_MQ_PASSWORD'),
                '/',
                array('verify_peer'=>false, 'verify_peer_name'=>false)
            );
            $channel = $connection->channel();

            $channel->exchange_declare($exchangeName, $exchangetype, false, true, false);


            $msg = new AMQPMessage($message, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

            $channel->basic_publish($msg, $exchangeName);
            $channel->close();*/


            $connection = new AMQPStreamConnection(env('RABBIT_MQ_HOST'), env('RABBIT_MQ_PORT'), env('RABBIT_MQ_USER'), env('RABBIT_MQ_PASSWORD'));

            $channel = $connection->channel();

            $channel->exchange_declare($exchangeName, $exchangetype, false, true, false);


            $msg = new AMQPMessage($message, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

            $channel->basic_publish($msg, $exchangeName);
            $channel->close();

        } catch (\Exception $exception){


        }
        finally{


            if ($connection != null){
                $connection->close();
            }

        }

    }
   /* use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected  $queueName;


    public function __construct($message, $queueName)
    {
        $this->message = $message;$this->queueName = $queueName;
    }


    public function handle()
    {
        $this->publishMessageToQueue($this->message, $this->queueName);
    }

    public function publishMessageToQueue($message, $queuename){


        $connection = null;


        try {


            $connection = new AMQPSSLConnection(
                env('RABBIT_MQ_HOST'),
                env('RABBIT_MQ_SSL_PORT'),
                env('RABBIT_MQ_USER'),
                env('RABBIT_MQ_PASSWORD'),
                '/',
                array('verify_peer'=>false)

            );

            $channel = $connection->channel();
            $channel->queue_declare($queuename, false, true, false, false);



            $msg = new AMQPMessage($message, array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));

            $channel->basic_publish($msg, '',$queuename);
            $channel->close();

        } catch (\Exception $exception){


            // manage failled event
        }
        finally{

            if ($connection != null){
                $connection->close();
            }

        }

    }*/
}
