<?php
$magRoot = realpath( __DIR__ . '/../..' );
require $magRoot . '/lib/Credis/Client.php';
//require $magRoot . '/lib/Zend/Cache/Backend/Interface.php';
//require $magRoot . '/lib/Zend/Cache/Backend/ExtendedInterface.php';
//require $magRoot . '/lib/Zend/Cache/Backend.php';
//require $magRoot . '/app/code/community/Cm/Cache/Backend/Redis.php';

$client = Redis_Connect( $magRoot . '/app/etc/local.xml' );
$client->flushdb();
exit;

function Redis_Connect( $xmlFile )
{
    if ( !is_readable( $xmlFile ) )
    {
        throw new Exception( sprintf('File "%s" does not exits or is not readable.', $xmlFile ) );
    }

    $xml  = simplexml_load_file( $xmlFile, 'SimpleXMLElement', LIBXML_NOCDATA );
    $host = strval( $xml->global->cache->backend_options->server );
    $port = strval( $xml->global->cache->backend_options->port );
    $db   = strval( $xml->global->cache->backend_options->database );
    if ( empty( $host ) )
    {
        throw new Exception( sprintf('Redis server hostname is not found in "%s".', $xmlFile ) );
    }
    if ( empty( $port ) )
    {
        throw new Exception( sprintf('Redis server port is not found in "%s".', $xmlFile ) );
    }
    if ( !strlen( $db ) )
    {
        throw new Exception( sprintf('Redis database number is not found in "%s".', $xmlFile ) );
    }

    if ( '/' == substr($host,0,1) )
    {
        // Socket
        $server = $host;
    }
    else
    {
        // TCP
        $server = sprintf( 'tcp://%s:%d', $host, $port );
    }

    $client = new Credis_Client( $server );
    $client->select( $db );

    return $client;
}

