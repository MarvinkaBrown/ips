<?php

namespace IPS\Output\UI;

/* To prevent PHP errors (extending class does not exist) revealing path */


if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}


abstract class Review extends Comment
{

}