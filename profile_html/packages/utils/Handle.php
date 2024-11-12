<?php

Class Handle {

	public static function make($index, $cmne, $label) {

		$list = str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');

		$parsed = (object) ['cmne' => $cmne];
		$table = (object) ['label' => $label, 'index' => array_search($index, $list)];

		return self::create($parsed, $table);
	}

	public static function create($parsed, $table) {

		$md5 = md5(microtime());
		if (isset($_SERVER['HOSTNAME'])) {
			$md5 = md5($_SERVER['HOSTNAME']);
		}
		if (isset($_SERVER['TERM_SESSION_ID'])) {
			$md5 = md5($_SERVER['TERM_SESSION_ID']);
		}
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$md5 = md5($_SERVER['REMOTE_ADDR']);
		}

		$base60 = str_split('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789');

		$dte = new DateTimeImmutable();
		$tbl = substr(strtolower($table->label), 0, 4);
		$idx = sprintf('%02s', (1 + $table->index) % 99);

		$name = isset($parsed->name) ? $parsed->name : 'tbd1';
		$ceml = isset($parsed->cmne) ? $parsed->cmne : $name;
		$ceml = strtolower(substr(md5($ceml), 0, 2));

		$dow = $dte->format('w');
		$woy = $dte->format('W');

		$dhr = $base60[intval($dte->format('H'))];
		$dmm = $base60[intval($dte->format('m'))];
		$dsc = $base60[intval($dte->format('i'))];

		$mcr = floor((float) microtime() * 2000);
		$mcs = $base60[$mcr % 60];

		$dow += intval($woy / 25) * 10;
		$coy = $woy % 25;

		$cli = $base60[hexdec(substr($md5, 0, 12)) % 60];

		$rnd = $cli . $dhr . $dmm . $dsc . $mcs;

		// todo: fix this for all projects
		$prj = $base60[0];
		if (isset($parsed->project)) {
			$prj = $parsed->project;
		}

		$when = $prj . $idx . $base60[$coy] . $base60[$dow];
		$what = $tbl;

		$ctr = $when . $ceml . $rnd;
		$crc = $base60[crc32($ctr) % 60];

		return $when . '-' . $what . '-' . $rnd . $crc . $ceml;

	}

}
