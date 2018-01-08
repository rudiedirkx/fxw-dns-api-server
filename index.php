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

if ( !isset($d['domain'], $d['type'], $d['name'], $d['value']) ) {
	err("Missing params");
}

if ( isset($d['add']) ) {
	$client = client();
	$domain = domain($client, $d['domain']);

	$record = new DnsRecord(0, $d['name'], $d['type'], $d['value'], @$d['ttl'] ?: 3600, @$d['prio'] ?: '');

	if ( !$client->addDnsRecord($domain, $record) ) {
		err("Couldn't delete. Don't know why.");
	}

	exit("Record added.\n");
}

elseif ( isset($d['delete']) ) {
	$client = client();
	$domain = domain($client, $d['domain']);

	$records = $client->getDnsRecords($domain);
	$record = array_reduce($records, function($result, DnsRecord $record) use ($d) {
		if ( $record->type == strtoupper($d['type']) && $record->name == $d['name'] && $record->value == $d['value'] ) {
			if ( empty($d['ttl']) || $d['ttl'] == $record->ttl ) {
				if ( empty($d['prio']) || $d['prio'] == $record->prio ) {
					return $record;
				}
			}
		}
		return $result;
	}, null);
	if ( !$record ) {
		err("No record with this type, name, value, (prio, ttl).");
	}

	if ( !$client->deleteDnsRecord($domain, $record) ) {
		err("Couldn't delete. Don't know why.");
	}

	exit("Record deleted.\n");
}

err("Only 'add' & 'delete' supported.");
