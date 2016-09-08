<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'etc_pagination';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.4.6';
$plugin['author'] = 'Oleg Loukianov';
$plugin['author_uri'] = 'http://www.iut-fbleau.fr/projet/etc/';
$plugin['description'] = 'Google-style pagination';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '3';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
// TXP 4.6 tag registration
	if(class_exists('\Textpattern\Tag\Registry')) Txp::get('\Textpattern\Tag\Registry')
		->register('etc_pagination')
		->register('etc_numpages')
		->register('etc_offset')
	;

if (@txpinterface == 'public') {
	register_callback('etc_pagination_url', 'pretext'); 
}

function etc_pagination_url($event, $step) {
	global $etc_pagination;
	$etc_pagination['url'] = preg_replace("|^https?://[^/]+|i","",serverSet('REQUEST_URI'));
}

function etc_pagination($atts, $thing='') {
	global $thispage, $etc_pagination;

	extract(lAtts(array(
		"root"=>null,
		"query"=>'',
		"pages"=>null,
//		"links"=>null,
		"page"=>null,
		"pgcounter"=>'pg',
		"offset"=>0,
		"range"=>-1,
		"scale"=>1,
		"mask"=>null,
		"link"=>'{*}',
		"current"=>'',
		"next"=>'',
		"prev"=>'',
		"first"=>null,
		"last"=>null,
		"gap"=>'&hellip;',
		"delimiter"=>',',
		"wraptag"=>'',
		"break"=>'',
		"class"=>'',
		"html_id"=>'',
		"atts"=>'',
		"reversenumberorder"=>'0'
	),$atts));

	$etc_pagination['pgcounter'] = $pgcounter;
	$cpages = 0;
	if(!isset($pages)) {
		$numberOfTabs = isset($thispage['numPages']) ? $thispage['numPages'] : 1;
		$pages = $numberOfTabs > 1 ? range(1, $numberOfTabs) : null;
	}
	elseif(strpos($pages, $delimiter) !== false) {
		$cpages = (strpos($pages, '::') !== false ? 2 : 1);
		$numberOfTabs = count($pages = do_list($pages, $delimiter));
	}
	elseif(strpos($pages, '..') !== false) {
		$cpages = 1;
		list($start, $end) = do_list($pages, '..');
		$numberOfTabs = count($pages = range($start, $end));
	} else {
		$numberOfTabs = intval($pages);
		$pages = range(1, $numberOfTabs);
	}

	if($numberOfTabs <= 1) return parse(EvalElse($thing, 0));

	# if we got tabs, start the outputting
	if(isset($links)) $links = array_pad(do_list($links, $delimiter), $numberOfTabs, '');
	elseif($cpages < 2)
		if($reversenumberorder) $links = array_reverse($pages); else $links = &$pages;
	else $links = array();

	$range = (int) $range;
	if($range < 0) $range = $numberOfTabs; else $range += 1;
	if($scale == 'auto') $scale = pow($numberOfTabs, 1/$range);
	else $scale = max(floatval($scale), 1);

	$out = $parts = array();
	$fragment = '';
	if($root === '') $hu = hu;
	elseif($root === null || $root[0] === '#') {$hu = strtok($etc_pagination['url'], '?'); $parts = $_GET; if($root) $fragment = $root;}
	else {
		$qs = parse_url($root);
		if(isset($qs['fragment'])) $root = str_replace($fragment = '#'.$qs['fragment'], '', $root);
		if(!empty($qs['query'])) parse_str(str_replace('&amp;', '&', $qs['query']), $parts);
		$hu = strtok($root, '?');
	}

	if($query) foreach(do_list($query, '&') as $qs) {
		@list($k, $v) = explode('=', $qs, 2);
		if(!isset($v)) if($k === '?') $parts = array();
			elseif($k === '#') $fragment = '';
			else unset($parts[$k]);
		else if($k === '#') $fragment = '#'.$v;
			elseif($k === '+') $hu .= $v;
			elseif($k[0] === '/') $hu = preg_replace($k, $v, $hu);
			else $parts[$k] = $v;
	}

	if(isset($page))
		if(!$cpages) $pgdefault = intval($page);
		elseif($cpages == 1)
			if(($pgdefault = array_search($page, $pages)) !== false) $pgdefault++;
			else $pgdefault = 0;
		else for($pgdefault = $numberOfTabs; $pgdefault > 0 && strpos($pages[$pgdefault-1].'::', $page.'::') !== 0; $pgdefault--);
	else $pgdefault = $reversenumberorder ? $numberOfTabs : 1;
	if(isset($parts[$pgcounter]))
		if($cpages < 2)
			if(($page = array_search($parts[$pgcounter], $links)) !== false) $page++;
			else $page = 0;
		else for($page = $numberOfTabs; $page > 0 && strpos($pages[$page-1].'::', $parts[$pgcounter].'::') !== 0; $page--);
	else $page = $pgdefault;
	$etc_pagination['page'] = $page;
	$page += $offset;
//	if($page < 1 || $page > $numberOfTabs) return parse(EvalElse($thing, 0));

	unset($parts[$pgcounter]);
	$qs = array();//join_qs($parts);
	foreach($parts as $k => $v) $qs[] = urlencode($k) . '=' . urlencode(is_array($v) ? implode(',', $v) : $v);
	$qs = '?'.implode('&amp;', $qs);
	$pagebase = $qs !== '?' ? $hu.$qs : $hu;
	if($qs !== '?') $qs .= '&amp;';
	$pageurl = $pgcounter ? $hu.$qs.$pgcounter.'=' : '';

	$currentclass = (empty($thing) && $current && strpos($link, '{current}') === false ? ($break ? 1 : -1) : 0);

	@list($gap1, $gap2) = explode($delimiter, $gap); if(!isset($gap2)) $gap2 = $gap1;
	@list($link, $link_) = explode($delimiter, $link, 2); if(!isset($link_)) $link_ = $link;
	foreach(array('first', 'prev', 'next', 'last', 'current') as $item) if(isset($$item))
		{@list($$item, ${$item.'_'}) = explode($delimiter, $$item, 2); if(!isset(${$item.'_'})) ${$item.'_'} = '';}
	if($currentclass) {if($current) $current = " class='$current'"; if($current_) $current_ = " class='$current_'";}

	$skip1 = $range < 3 ? $range : 1 + ($gap1 ? 1 : 0) + (isset($first) ? 0 : 1);
	$skip2 = $range < 3 ? $range : 1 + ($gap2 ? 1 : 0) + (isset($last) ? 0 : 1);
	if($numberOfTabs < 2*$range) {$loopStart = 1; $loopEnd = $numberOfTabs;}
	elseif($page <= $range) {$loopStart = 1; $loopEnd = 2*$range - $skip2;}
	elseif($page > $numberOfTabs - $range) {$loopStart = $numberOfTabs - 2*$range + $skip1 + 1; $loopEnd = $numberOfTabs;}
	else {$loopStart = $page - $range + $skip1; $loopEnd = $page + $range - $skip2;}

	if($custom = isset($mask)) {
		if(isset($thing)) {$link = str_replace('{link}', $link, $thing); $link_ = str_replace('{link}', $link_, $thing);}
		$thing = $mask;
	}
	elseif(!isset($thing)) {
		if($link) $link = '<a href="{href}" data-rel="{rel}"'.($currentclass < 0 ? '{current}' : '').'>'.$link.'</a>';
		if($link_) $link_ = '<span data-rel="self"'.($currentclass < 0 ? '{current}' : '').'>'.$link_.'</span>';
		foreach(array('first', 'prev', 'next', 'last', 'gap1', 'gap2') as $item) {
			if(!empty($$item)) $$item = '<a href="{href}" rel="{rel}" title="{*}">'.$$item.'</a>';
			if(!empty(${$item.'_'})) ${$item.'_'} = '<span data-rel="'.$item.'">'.${$item.'_'}.'</span>';
		}
		$thing = '{link}';
	}
	else $thing = EvalElse($thing, 1);
	$replacements = array_fill_keys(array('{*}', '{#}', '{$}', '{href}', '{rel}', '{link}'), '');
	$replacements['{pages}'] = $numberOfTabs;
	$replacements['{current}'] = $current_;
	$mask = array_fill_keys(array('{links}', '{first}', '{prev}', '{next}', '{last}', '{<+}', '{+>}'), '');

	$outfirst = $outprev = $outgap = '';
	if($prev || $prev_) {
		etc_pagination_link($replacements, $links, $pages, $page-1, $pgdefault, $pagebase, $pageurl, $fragment, 'prev', $cpages>1);
		if($page <= 1) $replacements['{#}'] = $replacements['{*}'] = '';
		$replacements['{link}'] = $mask['{prev}'] = strtr($page > 1 ? $prev : $prev_, $replacements);
		if(!$custom && $replacements['{link}']) $outprev = strtr($thing, $replacements);
	}

	if($loopStart > 1 && $range > 1 || isset($first)) {
		etc_pagination_link($replacements, $links, $pages, 1, $pgdefault, $pagebase, $pageurl, $fragment, '', $cpages>1);
		$replacements['{link}'] = $mask['{first}'] = strtr(isset($first) ? ($page > 1 ? $first : $first_) : $link, $replacements);
		if(!$custom && $replacements['{link}']) $outfirst = strtr($thing, $replacements);
		if($gap1 && $loopStart > 1) {
//			if($custom) $mask['{<+}'] = $gap1; else $outgap = $gap1;
			$i = $loopStart-$range-1+$skip2;
			if($scale > 1 && $i >= $scale) {
				$n = pow($scale, min(floor(log($i, $scale)), ceil(log($numberOfTabs - $i + 1, $scale)))); $i = intval(floor($i/$n)*$n);
			}
			$i = max($range ? 2 : 1, $i);
			etc_pagination_link($replacements, $links, $pages, $i, $pgdefault, $pagebase, $pageurl, $fragment, 'prev', $cpages>1);
			if($replacements['{link}'] = strtr($gap1, $replacements))
				if($custom) $mask['{<+}'] .= strtr($thing, $replacements); else $outgap = strtr($thing, $replacements);
		}
	}

	if($first) {
		if($outfirst) $out[] = $outfirst; if($outprev) $out[] = $outprev;
	} else {
		if($outprev) $out[] = $outprev; if($outfirst) $out[] = $outfirst;
	}
	if($outgap) $out[] = $outgap;

	if($link || $link_) for($i=$loopStart; $i<=$loopEnd; $i++) {
		etc_pagination_link($replacements, $links, $pages, $i, $pgdefault, $pagebase, $pageurl, $fragment, $i == $page-1 ? 'prev' : ($i == $page+1 ? 'next' : ''), $cpages>1);
		$self = $i == $page;
		$replacements['{current}'] = $self ? $current : $current_;
		if($replacements['{link}'] = strtr($self ? $link_ : $link, $replacements))
			if($custom) $mask['{links}'] .= $replacements['{link}'];
			else $out[] = ($currentclass > 0 ? ($self ? '{current}' : '{current_}') : '').strtr($thing, $replacements);
	}

	$outlast = $outnext = $outgap = '';
	$replacements['{current}'] = $current_;
	if($loopEnd < $numberOfTabs && $range > 1 || isset($last)) {
		etc_pagination_link($replacements, $links, $pages, $numberOfTabs, $pgdefault, $pagebase, $pageurl, $fragment, '', $cpages>1);
		$replacements['{link}'] = $mask['{last}'] = strtr(isset($last) ? ($page < $numberOfTabs ? $last : $last_) : $link, $replacements);
		if(!$custom && $replacements['{link}']) $outlast = strtr($thing, $replacements);
		if($gap2 && $loopEnd < $numberOfTabs) {
//			if($custom) $mask['{+>}'] = $gap2; else $outgap = $gap2;
			$i = $loopEnd+$range+1-$skip1;
			if($scale > 1) {
				$n = pow($scale, floor(log($numberOfTabs, $scale)));
				$nt = ceil($numberOfTabs/$n)*$n;
				if($nt >= $scale + $i - 1) {
					$n = pow($scale, min(floor(log($nt - $i + 1, $scale)), ceil(log($i, $scale))));
					do {
						$j = intval($nt - floor(($nt - $i + 1)/$n)*$n);
						$n /= $scale;
					} while($j >= $numberOfTabs && $n >= 1);
					$i = $j;
				}
			}
			$i = min($range ? $numberOfTabs-1 : $numberOfTabs, $i);

			etc_pagination_link($replacements, $links, $pages, $i, $pgdefault, $pagebase, $pageurl, $fragment, 'next', $cpages>1);
			if($replacements['{link}'] = strtr($gap2, $replacements))
				if($custom) $mask['{+>}'] = strtr($thing, $replacements); else $outgap = strtr($thing, $replacements);

		}
	}

	if($next || $next_) {
		etc_pagination_link($replacements, $links, $pages, $page+1, $pgdefault, $pagebase, $pageurl, $fragment, 'next', $cpages>1);
		if($page >= $numberOfTabs) $replacements['{#}'] = $replacements['{*}'] = '';
		$replacements['{link}'] = $mask['{next}'] = strtr($page < $numberOfTabs ? $next : $next_, $replacements);
		if(!$custom && $replacements['{link}']) $outnext = strtr($thing, $replacements);
	}

	if($outgap) $out[] = $outgap;
	if($last) {
		if($outnext) $out[] = $outnext; if($outlast) $out[] = $outlast;
	} else {
		if($outlast) $out[] = $outlast; if($outnext) $out[] = $outnext;
	}

	if($atts) $atts = ' '.$atts;
	if($custom) $out = array(strtr($thing, $mask));
	if($reversenumberorder) $out = array_reverse($out);
	$out = doWrap($out, $wraptag, $break, $class, '', $atts, '', $html_id);
	if($currentclass > 0) $out = str_replace(array("<$break>{current}", "<$break>{current_}"), array("<{$break}{$current}>", "<{$break}{$current_}>"), $out);
	return parse($out);
}

function etc_pagination_link(&$replacements, $links, $pages, $page, $pgdefault, $pagebase, $pageurl, $fragment, $rel, $custom) {
		if (isset($pages[$page-1])) if($custom) @list($replacements['{#}'], $replacements['{*}']) = explode('::', $pages[$page-1].'::'.$pages[$page-1]);
			else {$replacements['{*}'] = $pages[$page-1]; $replacements['{#}'] = $links[$page-1];}
		$replacements['{$}'] = $page;
//		if($reversenumberorder) $replacements['{$}'] = $numberOfTabs-$replacements['{$}']+1;
		$replacements['{href}'] = ($replacements['{$}'] == $pgdefault ? $pagebase : $pageurl.$replacements['{#}']).$fragment;
		$replacements['{rel}'] = $rel;
}


// -------------------------------------------------------------
	function etc_numpages($atts)
	{
		global $pretext, $prefs, $thispage, $etc_pagination, $etc_pagination_total;
		if(empty($atts) && isset($thispage)) {$etc_pagination_total = $etc_pagination['total'] = $thispage['total']; return empty($thispage['numPages']) ? 1 : $thispage['numPages'];}
		extract($pretext);
		if(empty($atts['table']) && !isset($atts['total'])) {
			$customFields = getCustomFields();
			$customlAtts = array_null(array_flip($customFields));
		} else $customFields = $customlAtts = array();

		//getting attributes
		extract(lAtts(array(
			'table'         => '',
			'total'         => null,
			'limit'         => 10,
			'pageby'        => '',
			'category'      => '',
			'section'       => '',
			'exclude'       => '',
			'include'       => '',
			'excerpted'     => '',
			'author'        => '',
			'realname'       => '',
			'month'         => '',
			'keywords'      => '',
			'expired'       => $prefs['publish_expired_articles'],
			'id'            => '',
			'time'          => 'past',
			'status'        => '4',
			'offset'        => 0
		)+$customlAtts, $atts));

		if(!($pageby = intval(empty($pageby) ? $limit : $pageby))) return 0;
		$etc_pagination['pageby'] = $pageby;
		if(isset($total)) return ceil(intval($total)/$pageby);

		$where = array("1");

		//Building query parts
		$category  = join("','", doSlash(do_list($category)));
		if($category) $where[] = !$table ? "(Category1 IN ('".$category."') or Category2 IN ('".$category."'))" : "category IN ('".$category."')";
		if($author) $where[] = (!$table ? "AuthorID" : "author")." IN ('".join("','", doSlash(do_list($author)))."')";
		if($id) $where[] = "ID IN (".join(',', array_map('intval', do_list($id))).")";
		if($status && (!$table || $table == 'file')) $where[] = 'Status in('.implode(',', doSlash(do_list($status))).')';
		if ($realname) {
			$authorlist = safe_column('name', 'txp_users', "RealName IN ('". join("','", doArray(doSlash(do_list($realname)), 'urldecode')) ."')" );
			$where[] = (!$table ? "AuthorID" : "author")." IN ('".join("','", doSlash($authorlist))."')";
		}
		if(!$table) {
			if($section) $where[] = "Section IN ('".join("','", doSlash(do_list($section)))."')";
			if($month) $where[] = "Posted like '".doSlash($month)."%'";
			if($excerpted=='y' || $excerpted=='1') $where[] = "Excerpt !=''";
			switch ($time) {
				case 'past':
					$where[] = "Posted <= now()"; break;
				case 'future':
					$where[] = "Posted > now()"; break;
			}
			if (!$expired) {
				$where[] = "(now() <= Expires or Expires IS NULL)";
			}
			//Allow keywords for no-custom articles. That tagging mode, you know
			if ($keywords) {
				$keys = doSlash(do_list($keywords));
				foreach ($keys as $key) {
					$keyparts[] = "FIND_IN_SET('".$key."',Keywords)";
				}
				$where[] = "(" . join(' or ',$keyparts) . ")";
			}
		} else {
			if($include) $where[] = "name IN ('".join("','", doSlash(do_list($include)))."')";
			if($exclude) $where[] = "name NOT IN ('".join("','", doSlash(do_list($exclude)))."')";
		}

		$customq = '';
		if ($customFields) {
			foreach($customFields as $cField) {
				if (isset($atts[$cField]))
					$customPairs[$cField] = $atts[$cField];
			}
			if(!empty($customPairs)) {
				$customq = buildCustomSql($customFields,$customPairs);
			}
		}

//		$where = "1=1" . $statusq. $time. $search . $id . $category . $section . $excerpted . $month . $author . $keywords . $customq;
		$where = implode(' AND ', $where) . $customq;

		//paginate
		$grand_total = safe_count($table ? 'txp_'.$table : 'textpattern', $where);
		$etc_pagination_total = $etc_pagination['total'] = $grand_total - $offset;
		return ceil($etc_pagination['total']/$pageby);
	}

	function etc_offset($atts)
	{
		global $etc_pagination;
		//getting attributes
		extract(lAtts((empty($etc_pagination) ? array() : $etc_pagination) + array(
			'type'        => '',
			'pageby'        => '10',
			'pgcounter'        => 'pg',
			'offset'        => '0'
		), $atts));

		if(!empty($etc_pagination)) extract($etc_pagination);
		$counter = isset($page) ? $page : urldecode(gps($pgcounter));
		$page = max(intval($counter), 1) + $offset;
		$max = isset($total) ? $total : $page*$pageby;
		switch($type) {
			case 'value' : return htmlspecialchars($counter, ENT_QUOTES);
			case 'page' : return $page;
			case 'start' : return min($max, ($page - 1)*$pageby + 1);
			case 'end' : return min($max, $page*$pageby);
			default : return ($page - 1)*$pageby;
		}
	}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. etc_pagination

This Textpattern plugin creates a paginated navigation bar similar to those seen on Google when you search for something. It has a wide variety of attributes - so you are able to customise it until you drop! It can be used alone for @<txp:article />@ and @<txp:article_custom />@ pagination, but it also has the ability to paginate any list, with a little help from "etc_query":http://www.iut-fbleau.fr/projet/etc/index.php?id=3.

Please report bugs and problems with this plugin at "the GitHub project's issues page":https://github.com/bloatware/etc-pagination/issues.

p(alert-block information). Note that this plugin creates a list that is meant to be _styled by you_ using CSS.

h2. Installation, upgrading and uninstallation

Download the latest version of the plugin from "the GitHub project page":https://github.com/bloatware/etc-pagination/releases, paste the code into the Textpattern Admin → Plugins panel, install and enable the plugin. Visit the "forum thread":http://forum.textpattern.com/viewtopic.php?id=39302 for more info or to report on the success or otherwise of the plugin.

To uninstall, delete from the Admin → Plugins panel.

h2. Usage

TODO

h2. Tags

h3. txp:etc_pagination

bc. <txp:etc_pagination />

TODO

h4. Attributes

* @atts="value"@<br />Additional attributes to apply to the @wraptag@ attribute value.
* @break="value"@<br />Where value is an HTML element, specified without brackets (e.g., @break="li"@) to separate list items.
* @class="class name"@<br />HTML @class@ to apply to the @wraptag@ attribute value.
* @current="text"@<br />A text active on the current tab.
* @delimiter="value"@<br />A string to use as delimiter in @general,current@ link pairs, see below. Default: @,@ (a comma).
* @first="text"@<br />Enables you to alter the text inside the 'first' link.
* @gap="text"@<br />One or two *delimiter*-separated symbols that state that there are more tabs before or after the ones currently viewable. Default: @…@.
* @html_id="id"@<br />The HTML @id@ attribute assigned to the @wraptag@ attribute value.
* @last="text"@<br />Enables you to alter the text inside the 'last' link.
* @link="text"@<br />Enables you to alter the text in the titles of the page tabs. If two *delimiter*-separated strings are given, then the first one will be used on @general@ pages, and the second one on the @current@ page. Default: @{*}@, where @{*}@ will be replaced by appropriate tab numbers, see 'Replacements' section below.
* @mask="value"@<br />If set, the whole output will be constructed by replacing the patterns @{links}@, @{first}@, @{last}@, @{prev}@, @{next}@, @{<+}@ (gap before) and @{+>}@ (gap after) by corresponding strings. Default: unset.
* @next="text"@<br />Enables you to alter the text inside the 'next' link.
* @offset="number"@<br />Page number offset. Default: @0@.
* @page="number"@<br />An integer to be considered as @default@ page (typically @1@).
* @pages="value"@<br />The total number of pages, or a range @start..end@, or a *delimiter*-separated list of @page[::title]@ items. Not needed when paginating @<txp:article />@ tag (default value).
* @pgcounter="value"@<br />The URL parameter to drive the navigation. Not needed when paginating @<txp:article />@ tag. Default: @pg@.
* @prev="text"@<br />Enables you to alter the text inside the 'previous' link.
* @range="number"@<br />The maximum number of left/right neighbours (including gaps) to display. If negative (default), all pages will be displayed. The plugin tries to avoid 'nonsense' gaps like @1 … 3 4@ and adjust the output so that the number of displayed tabs is @2*range+1@.
* @reversenumberorder="boolean"@<br />Makes it possible to reverse the numbers in the tabs. Setting to value to @0@ (default) renders @1,2,3 and so on@, setting value to @1@ renders @3,2,1 and so on@.
* @root="URL"@<br />The URL to be used as base for navigation, defaults to the current page URL.
* @scale="1"@<br />An integer to be used as grid for 'gap' links.
* @wraptag="element"@<br />HTML element to wrap (markup) block, specified without brackets (e.g., @wraptag="ul"@).

h4. Replacements

If you are not happy with the default @<a>@ links, use @<etc_pagination />@ as container to construct your own links. The following replacement tokens are available inside @etc_pagination@:

* @{$}@<br />The absolute page number.
* @{#}@<br />The displayed page number.
* @{*}@<br />The page title.
* @{current}@<br />The text given by @current@ attribute, enabled only on the current tab.
* @{href}@<br />The page URL.
* @{link}@<br />The text given by @link@ attribute, replaced by @first@, @prev@, etc when necessary.
* @{pages}@<br />The total pages number.
* @{rel}@<br />The page relation (@next@, @prev@).

For example, the following will generate a <code>select</code> pagination list:

bc. <txp:etc_pagination link="Page {*}" current="selected"
    wraptag="select" atts="name='pg'">
    <option value='{*}' {current}>{link}</option>
</txp:etc_pagination>

h4. Examples

h5. Example 1

bc. <txp:etc_pagination range="2" prev="Previous" next="Next"  wraptag="ul" break="li" />

This outputs if there are ten pages and we are on the third one, like so:

bc. <ul>
    <li>
        <a href="http://example.com/blog/&pg=1" rel="prev">Previous</a>
    </li>
    <li>
        <a href="http://example.com/blog/&pg=1">1</a>
    </li>
    <li>
        <a href="http://example.com/blog/&pg=2">2</a>
    </li>
    <li>
        <span data-rel="current">3</span>
    </li>
    <li>
        <span data-rel="gap">…</span>
    </li>
    <li>
        <a href="http://example.com/blog/&pg=10" rel="last">10</a>
    </li>
    <li>
        <a href="http://example.com/blog/&pg=5" rel="next">Next</a>
    </li>
</ul>

The @<a>@ and @<span>@ tags linking to @first@, @prev@, @current@, @next@, @last@, @gap@ pages, will be given the corresponding value of @rel@ or @data-rel@ attributes.

h5. Example 2

bc. <txp:etc_pagination range="0">
    <p>Page {*} of {pages}</p>
</txp:etc_pagination>

This outputs if there are ten pages and we are on the third one, like so:

bc. <p>Page 3 of 10</p>

h5. Example 3

TODO

h2. History

Please see the "changelog on GitHub":https://github.com/bloatware/etc-pagination/blob/master/CHANGELOG.textile.

h2. Authors/credits

Written by "Oleg Loukianov":http://www.iut-fbleau.fr/projet/etc/. Many thanks to "all additional contributors":https://github.com/bloatware/etc-pagination/graphs/contributors.
# --- END PLUGIN HELP ---
-->
<?php
}
?>
