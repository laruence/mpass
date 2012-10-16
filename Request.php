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
 * Mpass_Request is a wrapper for a client socket
 * and define the communication protocol
 *<code>
 * Read:
 * while (!$request->eof()) {
 *    $input .= $request->read($length);
 * }
 *
 * Write:
 * $len = $request->write($data);
 * //write do not send to client immediately
 * //we need call flush after write
 * $request->flush();
 *</code>
 *
 * @package Mpass
 */
class Mpass_Request {

    /** client name 
     *  eg: 10.23.33.158:3437
     */
    public  $name    = NULL;

	private $_socket = NULL;
	private $_pos    = 0;

    public  $initialized = FALSE;

	public function __construct($client) {
		if (!is_resource($client)) {
            return;
		}

		$this->_socket = $client;
        $this->name    = stream_socket_get_name($client, TRUE);

        $this->initialized = TRUE;
	}

	public function read($length = 1024) {
		$data = stream_socket_recvfrom($this->_socket, $length);
		$len  = strlen($data);
		$this->_pos += $len;

		return $data;
	}

	public function peek($length = 1) {
		return stream_socket_recvfrom($this->_socket, 1, STREAM_PEEK);
	}

    /**
     * send data to client
     */
	public function write($data) {

		$data 	= strval($data);
		$length = strlen($data);

		if ($length == 0) {
            return 0;
		}

        /* in case of send failed */
        $alreay_sent = 0;
        while ($alreay_sent < $length) {
            if (($send = stream_socket_sendto($this->_socket, substr($data, $alreay_sent))) < 0) {
                break;
            }
            $alreay_sent += $send;
        }

        return $length;
    }

    public function name() {
        return $this->name;
    }

	public function __destruct() {
        /** in case of unset socket in user script
         *  we need do this in Server side */
        /* stream_socket_shutdown($this->_socket, STREAM_SHUT_RDWR); */
	}
}

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
