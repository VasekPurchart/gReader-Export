#!/usr/bin/env php
<?php

define('COOKIEHEADER_FILE', __DIR__ . '/cookieheader.conf');

define('DATA_DIR', __DIR__ . '/data');

define('SUBSCRIPTIONS_URL', 'http://www.google.com/reader/api/0/subscription/list?output=json');
define('SUBSCRIPTIONS_FILE', DATA_DIR . '/subscriptions.json');

define('FEED_BASE_URL', 'http://www.google.com/reader/atom/');
define('FEED_LIMIT', 2147483647); // hard limit given by API

define('CONTINUATION_SEARCH_PART', 1000); // limit of chars from beggining of XML where continuation is searched

function loadCookieHeader() {
	if (!file_exists(COOKIEHEADER_FILE)) {
		println('Create file ' . COOKIEHEADER_FILE . ' with Cookie header information');
		exit(1);
	}
	define('COOKIEHEADER', file_get_contents(COOKIEHEADER_FILE));
}

function getApiResponse(\HttpRequest $request) {
	$request->setHeaders(array(
		'Cookie' => COOKIEHEADER,
	));
	$response = $request->send();
	if ($response->getResponseCode() !== 200) {
		println('Server response: ' . $response->getResponseStatus() . ' (' . $response->getResponseCode() . ')');
		exit(1);
	}

	return $response;
}

function getContinuation($string) {
	if (preg_match('~.*<gr:continuation>(?P<cont>[0-9a-zA-Z]*)</gr:continuation>.*~', $string, $matches)) {
		return $matches['cont'];
	}

	return null;
}

function downloadFeed($url, $dir, $order, $continuation = null) {
	println('> ' . $order);
	$downloadUrl = ($continuation !== null) ? $url . '&c=' . $continuation : $url;
	$feedResponse = getApiResponse(new \HttpRequest($downloadUrl));
	$feedData = $feedResponse->getBody();
	file_put_contents($dir . '/' . $order . '.xml', $feedData);

	$nextContinuation = getContinuation(substr($feedData, 0, CONTINUATION_SEARCH_PART));
	if ($nextContinuation !== null) {
		downloadFeed($url, $dir, $order + 1, $nextContinuation);
	}
}

function println($string) {
	echo $string . "\n";
}

loadCookieHeader();
$subscriptionsResponse = getApiResponse(new \HttpRequest(SUBSCRIPTIONS_URL));

$subscriptionsData = $subscriptionsResponse->getBody();
file_put_contents(SUBSCRIPTIONS_FILE, $subscriptionsData);

$jsonSubscriptions = json_decode($subscriptionsData);
foreach ($jsonSubscriptions->subscriptions as $subscription) {
	println($subscription->id);
	$feedUrl = urlencode($subscription->id);
	$dir = DATA_DIR . '/' . $feedUrl;
	if (file_exists($dir)) {
		continue;
	}
	mkdir($dir);
	$downloadUrl = FEED_BASE_URL . $feedUrl . '?n=' . FEED_LIMIT;
	downloadFeed($downloadUrl, $dir, 0);
}
