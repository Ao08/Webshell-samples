<?php

/**
 * Compares a previous version of a Model object to the
 * current version, allowing you to restore the previous
 * version if desired.
 */

$page->layout = 'admin';

if (! User::require_admin ()) {
	$this->redirect ('/admin');
}

if (! isset ($_GET['current'])) {
	$this->redirect ('/admin');
}

if (! in_array ($_GET['current'], array ('yes', 'no'))) {
	$this->redirect ('/admin');
}

$is_current = ($_GET['current'] === 'yes') ? true : false;
$is_deleted = false;

$ver = new Versions ($_GET['id']);
$old = $ver->restore ();
$class = $ver->class;
$cur = new $class ($ver->pkey);
if ($cur->error) {
	// deleted item
	$is_deleted = true;

	foreach (json_decode ($ver->serialized) as $key => $value) {
		$cur->{$key} = $value;
	}
}
$diff = Versions::diff ($old, $cur);
$data = array ();
$cur_orig = (array) $cur->orig ();
$old_orig = (array) $old->orig ();
foreach ($cur_orig as $key => $value) {

	// Skip sensitive user data
	if ($class === 'User') {
		if ($key === 'password' || $key === 'expires' || $key === 'session_id') {
			continue;
		}
	}
	
	// Only allow certain fields to contain html
	$is_html = false;
	if ($class === 'Block' && $key === 'body') {
		$is_html = true;
	} else if ($class === 'Webpage' && $key === 'body') {
		$is_html = true;
	} else if ($class === 'blog\Post' && $key === 'body') {
		$is_html = true;
	} else if ($class === 'Event' && $key === 'details') {
		$is_html = true;
	}
	
	// Sanitize non-html output
	if (! $is_html) {
		$value = Template::sanitize ($value);
		$old_orig[$key] = Template::sanitize ($old_orig[$key]);
	}

	$data[$key] = array (
		'cur' => $value,
		'old' => $old_orig[$key],
		'diff' => (in_array ($key, $diff)) ? true : false,
		'html' => $is_html
	);
}

if (is_subclass_of ($cur, 'ExtendedModel')) {
	unset ($data[$cur->_extended_field]);
}

$page->title = __ ('Comparing') . ' ' . Template::sanitize (__ (Versions::display_name ($ver->class))) . ' / ' . $ver->pkey;

echo $tpl->render ('admin/compare', array (
	'fields' => $data,
	'class' => $ver->class,
	'pkey' => $ver->pkey,
	'ts' => $ver->ts,
	'is_current' => $is_current,
	'is_deleted' => $is_deleted
));
