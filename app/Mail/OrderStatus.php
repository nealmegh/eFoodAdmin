<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatus extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $order_id;
    protected $status;

    public function __construct($order_id,$status)
    {
        $this->order_id = $order_id;
        $this->status  = $status;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $order_id = $this->order_id;
        $status = $this->status;
        return $this->view('email-templates.custome-order-status', ['order_id' => $order_id,'status'=>$status]);
    }
}
