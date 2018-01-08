<?php

use rdx\fxwdns\Client;
use rdx\fxwdns\DnsRecord;
use rdx\fxwdns\WebAuth;

require __DIR__ . '/vendor/autoload.php';

header('Content-type: text/plain; charset=utf-8');

function err($msg) {
	header('HTTP/1.1 400 Invalid');
	exit("$msg\n");
}

function client() {
	$client = new Client(new WebAuth($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']));
	if ( !$client->logIn() ) {
		err("Invalid credentials.");
	}
	return $client;
}

function domain( Client $client, $name ) {
	$domain = $client->getDomain($name);
	if ( !$domain ) {
		err("Invalid domain '" . $name . "'.");
	}
	return $domain;
}

if ( !isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) ) {
	err("Need user & pass.");
}

$d = $_POST;

if ( !isset($d['domain'], $d['type'], $d['name']) ) {
	err("Missing params");
}

if ( isset($d['add']) ) {
	if ( !isset($d['value']) ) {
		err("Missing params");
	}

	$client = client();
	$domain = domain($client, $d['domain']);

	$record = new DnsRecord(0, $d['name'], $d['type'], $d['value'], @$d['ttl'] ?: 3600, @$d['prio'] ?: '');

	if ( !$client->addDnsRecord($domain, $record) ) {
		err("Couldn't add. Don't know why.");
	}

	exit("Record added.\n");
}

elseif ( isset($d['delete']) ) {
	$client = client();
	$domain = domain($client, $d['domain']);

	$conditions = array_intersect_key($d, array_flip(['type', 'name', 'value', 'ttl', 'prio']));
	$conditions['type'] = strtoupper($conditions['type']);
	$records = $client->findDnsRecords($domain, $conditions);

	$deleted = 0;
	foreach ( $records as $record ) {
		if ( $client->deleteDnsRecord($domain, $record) ) {
			$deleted++;
		}
	}

	if ( $deleted == 0 && count($records) > 0 ) {
		err("Couldn't delete. Don't know why.");
	}
	elseif ( $deleted < count($records) ) {
		err("Only deleted $deleted / " . count($records) . " records.");
	}

	exit("Deleted $deleted records.\n");
}

err("Only 'add' & 'delete' supported.");
