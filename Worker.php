<?php
/*
----------------------------------------------------
Mpass - Multi-Process Socket Server for PHP

copyright (c) 2010 Laruence
http://www.laruence.com

If you have any questions or comments, please email:
laruence@yahoo.com.cn

*/

/**
 * Executor Class Interface, User defined executor class must implements this interface.
 * there is only one method "execute" which take 1 parameters input which is the input
 * binary stream contents 
 * 
 * @package Mapss
 * @version 0.1.0
 */
interface Mpass_IExecutor {
	public function execute(Mpass_Request $request);
}


/**
 * Mpass Work class
 * a mid-layer between server and executor
 * and this is work in child process 
 * no need to worry about memory leak
 * @package Mpass
 */
class Mpass_Worker {

    /* Mpass_IExecutor instance 
     * must have a method execute
     */
	private $_executor = NULL;

	public function __construct(Mpass_IExecutor $executor) {
		$this->_executor = $executor;
	}

	public function run($client) {

        $request = new Mpass_Request($client);

        if (FALSE === $request->initialized) {
            Mpass_Log::err("initialized request failed, client \"". stream_socket_get_name($client) . "\"", __METHOD__);
            return FALSE;
        }

        /* set timeout */
        set_time_limit(30);

        /* ignore all quit signal */
		$signals = array(
            SIGINT  => "SIGINT",
            SIGHUP  => "SIGHUP",
            SIGQUIT => "SIGQUIT",
		);

		foreach ($signals as $signal => $name) {
			pcntl_signal($signal, SIG_IGN);
		}

        $ret = $this->_executor->execute($request);

        if (version_compare(phpversion(), "5.2.1", "ge")) {
            stream_socket_shutdown($client, STREAM_SHUT_RDWR);
        } else {
            fclose($client);
        }

        unset($request);

		return TRUE;
	}
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
