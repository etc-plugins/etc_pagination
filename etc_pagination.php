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

$plugin['version'] = '0.4.3';
$plugin['author'] = 'Oleg Loukianov';
$plugin['author_uri'] = 'http://www.iut-fbleau.fr/projet/etc/';
$plugin['description'] = 'Google-style pagination';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

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
function etc_pagination($atts, $thing='') {
	global $thispage, $pretext;

	extract(lAtts(array(
		"root"               => null,
		"pages"              => null,
		"page"               => null,
		"pgcounter"          => 'pg',
		"offset"             => 0,
		"range"              => -1,
		"mask"               => null,
		"link"               => '{*}',
		"current"            => '',
		"next"               => '',
		"prev"               => '',
		"first"              => null,
		"last"               => null,
		"gap"                => '&hellip;',
		"delimiter"          => ',',
		"wraptag"            => '',
		"break"              => '',
		"class"              => '',
		"html_id"            => '',
		"atts"               => '',
		"reversenumberorder" => '0',
	),$atts));

	if(!isset($pages)) {
		$pages = isset($thispage['numPages']) ? $thispage['numPages'] : 1;
	}

	if($cpages = (strpos($pages, $delimiter) !== false)) {
		$numberOfTabs = count($pages = do_list($pages, $delimiter));
	} else $numberOfTabs = intval($pages); //$pages = range(1, $numberOfTabs = intval($pages));

	if($numberOfTabs <= 1) return parse(EvalElse($thing, 0));
	if($reversenumberorder && $cpages) $pages = array_reverse($pages);

	# if we got tabs, start the outputting
	$range = intval($range);
	if($range < 0) $range = $numberOfTabs; else $range += 1;

	$out = $parts = array();
//	$hu = '';// $pretext['path_from_root'];
	if($root === null) {$hu = strtok($pretext['request_uri'], '?')/*''*/; $parts = $_GET;}
	elseif($root === '') $hu = hu;
	else {
		extract(parse_url($root));
		if(!empty($query)) parse_str(str_replace('&amp;', '&', $query), $parts);
		$hu = (isset($scheme) ? $scheme.'://' : '') . (isset($host) ? $host : '') . (isset($path) ? $path : '');
	}

	if(isset($page))
		if(!$cpages) $pgdefault = $reversenumberorder ? $numberOfTabs - intval($page) + 1 : intval($page);
		else for($pgdefault = $numberOfTabs; $pgdefault > 1 && strpos($pages[$pgdefault-1].'::', $page.'::') !== 0; $pgdefault--);
	else $pgdefault = $reversenumberorder ? $numberOfTabs : 1;
	if(isset($parts[$pgcounter]))
		if(!$cpages) $page = $reversenumberorder ? $numberOfTabs - intval($parts[$pgcounter]) + 1 : intval($parts[$pgcounter]);
		else for($page = $numberOfTabs; $page > 1 && strpos($pages[$page-1].'::', $parts[$pgcounter].'::') !== 0; $page--);
	else $page = $pgdefault;
	$page += $offset;
	if($page < 1 || $page > $numberOfTabs) return parse(EvalElse($thing, 0));
	if($cpages) $pgdefault = $pages[$pgdefault - 1];

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
		if($link) $link = '<a href="{href}" rel="{rel}"'.($currentclass < 0 ? '{current}' : '').'>'.$link.'</a>';
		if($link_) $link_ = '<span data-rel="self"'.($currentclass < 0 ? '{current}' : '').'>'.$link_.'</span>';
		foreach(array('first', 'prev', 'next', 'last') as $item) {
			if(!empty($$item)) $$item = '<a href="{href}" rel="{rel}">'.$$item.'</a>';
			if(!empty(${$item.'_'})) ${$item.'_'} = '<span data-rel="'.$item.'">'.${$item.'_'}.'</span>';
		}
		if($gap1) $gap1 = '<span data-rel="gap">'.$gap1.'</span>';
		if($gap2) $gap2 = '<span data-rel="gap">'.$gap2.'</span>';
		$thing = '{link}';//'<a href="{href}" rel="{rel}" data-rel="{rel}">{link}</a>';
	}
	else $thing = EvalElse($thing, 1);
	$replacements = array_fill_keys(array('{*}', '{#}', '{href}', '{rel}', '{link}'), '');
	$replacements['{pages}'] = $numberOfTabs;
	$replacements['{current}'] = $current_;
	$mask = array_fill_keys(array('{links}', '{first}', '{prev}', '{next}', '{last}', '{<+}', '{+>}'), '');

	$outfirst = $outprev = $outgap = '';
	if($prev || $prev_) {
		if($page > 1) if($cpages) @list($replacements['{#}'], $replacements['{*}']) = explode('::', $pages[$page-2].'::'.$pages[$page-2]);
			else $replacements['{#}'] = $replacements['{*}'] = $reversenumberorder ? $numberOfTabs - $page + 2 : $page - 1;//$pages[$page-2];
		else $replacements['{#}'] = $replacements['{*}'] = '';
		$replacements['{href}'] = $replacements['{#}']  == $pgdefault ? $pagebase : $pageurl.$replacements['{#}'];
		$replacements['{rel}'] = 'prev';
		$replacements['{link}'] = $mask['{prev}'] = strtr($page > 1 ? $prev : $prev_, $replacements);
		if(!$custom && $replacements['{link}']) $outprev = strtr($thing, $replacements);
	}

	if($loopStart > 1 && $range > 1 || isset($first)) {
		if($cpages) @list($replacements['{#}'], $replacements['{*}']) = explode('::', $pages[0].'::'.$pages[0]);
		else $replacements['{#}'] = $replacements['{*}'] = $reversenumberorder ? $numberOfTabs : 1;//$pages[0];
		$replacements['{href}'] = $replacements['{#}'] == $pgdefault ? $pagebase : $pageurl.$replacements['{#}'];
		$replacements['{rel}'] = '';
		$replacements['{link}'] = $mask['{first}'] = strtr(isset($first) ? ($page > 1 ? $first : $first_) : $link, $replacements);
		if(!$custom && $replacements['{link}']) $outfirst = strtr($thing, $replacements);
		if($gap1 && $loopStart > 1)
			if($custom) $mask['{<+}'] = $gap1; else $outgap = $gap1;
	}

	if($first) {
		if($outfirst) $out[] = $outfirst; if($outprev) $out[] = $outprev;
	} else {
		if($outprev) $out[] = $outprev; if($outfirst) $out[] = $outfirst;
	}
	if($outgap) $out[] = $outgap;

	if($link || $link_) for($i=$loopStart; $i<=$loopEnd; $i++) {
		if($cpages) @list($replacements['{#}'], $replacements['{*}']) = explode('::', $pages[$i-1].'::'.$pages[$i-1]);
		else $replacements['{#}'] = $replacements['{*}'] = $reversenumberorder ? $numberOfTabs - $i + 1 : $i;//$pages[$i-1];
		$replacements['{href}'] = $replacements['{#}'] == $pgdefault ? $pagebase : $pageurl.$replacements['{#}'];
		$self = $i == $page;
		$replacements['{rel}'] = $i == $page-1 ? 'prev' : ($i == $page+1 ? 'next' : '');
		$replacements['{current}'] = $self ? $current : $current_;
		if($replacements['{link}'] = strtr($self ? $link_ : $link, $replacements))
			if($custom) $mask['{links}'] .= $replacements['{link}'];
			else $out[] = ($currentclass > 0 ? ($self ? '{current}' : '{current_}') : '').strtr($thing, $replacements);
	}

	$outlast = $outnext = $outgap = '';
	$replacements['{current}'] = $current_;
	if($loopEnd < $numberOfTabs && $range > 1 || isset($last)) {
		if($cpages) @list($replacements['{#}'], $replacements['{*}']) = explode('::', $pages[$numberOfTabs-1].'::'.$pages[$numberOfTabs-1]);
		else $replacements['{#}'] = $replacements['{*}'] = $reversenumberorder ? 1 : $numberOfTabs;//$pages[$numberOfTabs-1];
		$replacements['{href}'] = $replacements['{#}'] == $pgdefault ? $pagebase : $pageurl.$replacements['{#}'];
		$replacements['{rel}'] = '';
		$replacements['{link}'] = $mask['{last}'] = strtr(isset($last) ? ($page < $numberOfTabs ? $last : $last_) : $link, $replacements);
		if($gap2 && $loopEnd < $numberOfTabs)
			if($custom) $mask['{+>}'] = $gap2; else $outgap = $gap2;
		if(!$custom && $replacements['{link}']) $outlast = strtr($thing, $replacements);
	}

	if($next || $next_) {
		if($page < $numberOfTabs) if($cpages) @list($replacements['{#}'], $replacements['{*}']) = explode('::', $pages[$page].'::'.$pages[$page]);
			else $replacements['{#}'] = $replacements['{*}'] = $reversenumberorder ? $numberOfTabs - $page : $page + 1;//$pages[$page];
		else $replacements['{#}'] = $replacements['{*}'] = '';
		$replacements['{href}'] = $replacements['{#}'] == $pgdefault ? $pagebase : $pageurl.$replacements['{#}'];
		$replacements['{rel}'] = 'next';
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
	$out = doWrap($out, $wraptag, $break, $class, '', $atts, '', $html_id);
	if($currentclass > 0) $out = str_replace(array("<$break>{current}", "<$break>{current_}"), array("<{$break}{$current}>", "<{$break}{$current_}>"), $out);
	return parse($out);
}

// -------------------------------------------------------------
	function etc_numpages($atts)
	{
		global $pretext, $prefs, $thispage, $etc_pagination_total;
		if(empty($atts) && isset($thispage)) {$etc_pagination_total = $thispage['total']; return empty($thispage['numPages']) ? 1 : $thispage['numPages'];}
		extract($pretext);
		if(empty($atts['table']) && !isset($atts['total'])) {
			$customFields = getCustomFields();
			$customlAtts = array_null(array_flip($customFields));
		} else $customFields = $customlAtts = array();

		//getting attributes
		extract(lAtts(array(
			'table'     => '',
			'total'     => null,
			'limit'     => 10,
			'pageby'    => '',
			'category'  => '',
			'section'   => '',
			'exclude'   => '',
			'include'   => '',
			'excerpted' => '',
			'author'    => '',
			'realname'  => '',
			'month'     => '',
			'keywords'  => '',
			'expired'   => $prefs['publish_expired_articles'],
			'id'        => '',
			'time'      => 'past',
			'status'    => '4',
			'offset'    => 0
		)+$customlAtts,$atts));

		$pageby = intval(empty($pageby) ? $limit : $pageby);
		if(isset($total)) return $pageby ? ceil(intval($total)/$pageby) : $total;

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
				$where[] = "(now() <= Expires or Expires = ".NULLDATETIME.")";
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
		$etc_pagination_total = $grand_total - $offset;
		return $pageby ? ceil($etc_pagination_total/$pageby) : 0;
	}

	function etc_offset($atts)
	{
		//getting attributes
		extract(lAtts(array(
			'type'      => '',
			'pageby'    => '10',
			'pgcounter' => 'pg',
			'offset'    => '0'
		),$atts));

		global $etc_pagination_total;
		$counter = urldecode(gps($pgcounter));
		$page = max(intval($counter), 1) + $offset;
		$max = isset($etc_pagination_total) ? $etc_pagination_total : $page*$pageby;
		switch($type) {
			case 'value' : return txpspecialchars($counter);
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
* @pages="value"@<br />The total number of pages, or a *delimiter*-separated list of @page[::title]@ items. Not needed when paginating @<txp:article />@ tag (default value).
* @pgcounter="value"@<br />The URL parameter to drive the navigation. Not needed when paginating @<txp:article />@ tag. Default: @pg@.
* @prev="text"@<br />Enables you to alter the text inside the 'previous' link.
* @range="number"@<br />The maximum number of left/right neighbours (including gaps) to display. If negative (default), all pages will be displayed. The plugin tries to avoid 'nonsense' gaps like @1 … 3 4@ and adjust the output so that the number of displayed tabs is @2*range+1@.
* @reversenumberorder="boolean"@<br />Makes it possible to reverse the numbers in the tabs. Setting to value to @0@ (default) renders @1,2,3 and so on@, setting value to @1@ renders @3,2,1 and so on@.
* @root="URL"@<br />The URL to be used as base for navigation, defaults to the current page URL.
* @wraptag="element"@<br />HTML element to wrap (markup) block, specified without brackets (e.g., @wraptag="ul"@).

h4. Replacements

If you are not happy with the default @<a>@ links, use @<etc_pagination />@ as container to construct your own links. The following replacement tokens are available inside @etc_pagination@:

* @{#}@<br />The page number.
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

This example outputs, if there are ten pages and we are on the third one, like so:

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

This example outputs, if there are 8 pages and we are on the sixth one, like so:

bc. <p>Page 6 of 8</p>

h5. Example 3

bc. <txp:etc_pagination wraptag="nav" class="paginator" range="3" atts=' aria-label="Blog navigation"'
    prev='<a class="prev" rel="prev" href="http://example.com{href}" title="Go to previous page" aria-label="Go to previous page">Prev</a>,
          <span class="prev disabled" aria-label="This is the first page">Prev</span>'
    next='<a class="next" rel="next" href="http://example.com{href}" title="Go to next page" aria-label="Go to next page">Next</a>,
          <span class="next disabled" aria-label="This is the last page">Next</span>'
    link='<li><a href="http://example.com{href}" title="Go to page {*}" aria-label="Go to page {*}">{*}</a></li>,
          <li class="current"><b title="Current page" aria-label="Current page">{*}</b></li>'
    gap='<li><span title="More pages" aria-label="More pages">…</span></li>'
    mask='{prev}{next}
    <ul class="pagination">
        {first}{<+}{links}{+>}{last}
    </ul>'
    />

Fully customised HTML solutions can be achieved - this example outputs, if there are 11 pages and we are on the fifth one, like so:

bc. <nav class="paginator" aria-label="Blog navigation">
    <a class="prev" rel="prev" href="http://example.com/blog/?pg=4" title="Go to previous page" aria-label="Go to previous page">Prev</a>
    <a class="next" rel="next" href="http://example.com/blog/?pg=6" title="Go to next page" aria-label="Go to next page">Next</a>
    <ul class="pagination">
        <li>
            <a href="http://example.com/blog/" title="Go to page 1" aria-label="Go to page 1">1</a>
        </li>
        <li>
            <span title="More pages" aria-label="More pages">…</span>
        </li>
        <li>
            <a href="http://example.com/blog/?pg=4" title="Go to page 4" aria-label="Go to page 4">4</a>
        </li>
        <li class="current">
            <b title="Current page" aria-label="Current page">5</b>
        </li>
        <li>
            <a href="http://example.com/blog/?pg=6" title="Go to page 6" aria-label="Go to page 6">6</a>
        </li>
        <li>
            <span title="More pages" aria-label="More pages">…</span>
        </li>
        <li>
            <a href="http://example.com/blog/?pg=11" title="Go to page 11" aria-label="Go to page 11">11</a>
        </li>
    </ul>
</nav>

h2. History

Please see the "changelog on GitHub":https://github.com/bloatware/etc-pagination/blob/master/CHANGELOG.textile.

h2. Authors/credits

Written by "Oleg Loukianov":http://www.iut-fbleau.fr/projet/etc/. Many thanks to "all additional contributors":https://github.com/bloatware/etc-pagination/graphs/contributors.
# --- END PLUGIN HELP ---
-->
<?php
}
?>
