<?php
/**
 * PHP debugging tool, It is an ultimate tool among the diagnostic ones.
 *
 * @package Advandz
 * @subpackage Advandz.debugger
 * @copyright Copyright (c) 2012-2017 CyanDark, Inc. All Rights Reserved.
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author The Advandz Team <team@advandz.com>
 */
namespace Tracy;

class OutputDebugger {
	const BOM = "\xEF\xBB\xBF";

	/** @var array of [file, line, output, stack] */
	private $list = [];

	public static function enable() {
		$me = new static;
		$me->start();
	}

	public function start() {
		foreach (get_included_files() as $file) {
			if (fread(fopen($file, 'r'), 3) === self::BOM) {
				$this->list[] = [$file, 1, self::BOM];
			}
		}
		ob_start([$this, 'handler'], 1);
	}

	/** @internal */
	public function handler($s, $phase) {
		$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		if (isset($trace[0]['file'], $trace[0]['line'])) {
			$stack = $trace;
			unset($stack[0]['line'], $stack[0]['args']);
			$i = count($this->list);
			if ($i && $this->list[$i - 1][3] === $stack) {
				$this->list[$i - 1][2] .= $s;
			} else {
				$this->list[] = [$trace[0]['file'], $trace[0]['line'], $s, $stack];
			}
		}
		if ($phase === PHP_OUTPUT_HANDLER_FINAL) {
			return $this->renderHtml();
		}
	}

	private function renderHtml() {
		$res = '<style>code, pre {white-space:nowrap} a {text-decoration:none} pre {color:gray;display:inline} big {color:red}</style><code>';
		foreach ($this->list as $item) {
			$stack = [];
			foreach (array_slice($item[3], 1) as $t) {
				$t += ['class' => '', 'type' => '', 'function' => ''];
				$stack[] = "$t[class]$t[type]$t[function]()" . (isset($t['file'], $t['line']) ? ' in ' . basename($t['file']) . ":$t[line]" : '');
			}

			$res .= '<span title="' . Helpers::escapeHtml(implode("\n", $stack)) . '">' . Helpers::editorLink($item[0], $item[1]) . ' ' . str_replace(self::BOM, '<big>BOM</big>', Dumper::toHtml($item[2])) . "</span><br>\n";
		}

		return $res . '</code>';
	}

}
?>