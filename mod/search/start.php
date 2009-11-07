<?php
/**
   * Elgg core search.
   *
   * @package Elgg
   * @subpackage Core
   * @author Curverider Ltd <info@elgg.com>, The MITRE Corporation <http://www.mitre.org>
   * @link http://elgg.org/
   */

/**
 * Initialise search helper functions.
 *
 */
function search_init() {
	global $CONFIG;
	require_once 'search_hooks.php';

	// page handler for search actions and results
	register_page_handler('search','search_page_handler');

	// register some default search hooks
	register_plugin_hook('search', 'object', 'search_objects_hook');
	register_plugin_hook('search', 'user', 'search_users_hook');

	// @todo pull this out into groups
	register_plugin_hook('search', 'group', 'search_groups_hook');

	// tags are a bit different.
	// register a custom search type and a hook for that.
	register_plugin_hook('search_types', 'get_types', 'search_custom_types_tags_hook');
	register_plugin_hook('search', 'tags', 'search_tags_hook');

	// get server min and max allowed chars for ft searching
	$word_lens = get_data('SELECT @@ft_min_word_len as min, @@ft_max_word_len as max');

	$CONFIG->search_info = array();
	$CONFIG->search_info['min_chars'] = $word_lens[0]->min;
	$CONFIG->search_info['max_chars'] = $word_lens[0]->max;

	// add in CSS for search elements
	extend_view('css', 'search/css');
}

/**
 * Page handler for search
 *
 * @param array $page Page elements from pain page handler
 */
function search_page_handler($page) {
	global $CONFIG;

	// if there is no q set, we're being called from a legacy installation
	// it expects a search by tags.
	// actually it doesn't, but maybe it should.
	// maintain backward compatibility
	if(!get_input('q', get_input('tag', NULL))) {
		set_input('q', $page[0]);
		//set_input('search_type', 'tags');
	}

	include_once('index.php');
}

/**
 * Return a string with highlighted matched elements.
 * Checks for "s
 * Provides context for matched elements.
 * Will not return more than $max_length of full context.
 * Only highlights words
 *
 * @param unknown_type $haystack
 * @param unknown_type $need
 * @param unknown_type $context
 * @param unknown_type $max_length
 * @return unknown_type
 */
function search_get_highlighted_relevant_substrings($haystack, $needle, $min_match_context = 15, $max_length = 250) {
	$haystack = strip_tags($haystack);
	$haystack_lc = strtolower($haystack);

	// for now don't worry about "s or boolean operators
	$needle = str_replace(array('"', '-', '+', '~'), '', stripslashes(strip_tags($needle)));
	$words = explode(' ', $needle);

	$min_chars = $CONFIG->search_info['min_chars'];
	// if > ft_min_word == not running in literal mode.
	if ($needle >= $min_chars) {
		// clean out any words that are ignored by mysql
		foreach ($words as $i => $word) {
			if (strlen($word) < $min_chars) {
				unset ($words[$i]);
			}
		}
	}

	$substr_counts = array();
	$str_pos = array();
	// get the full count of matches.
	foreach ($words as $word) {
		$word = strtolower($word);
		$count = substr_count($haystack, $word);
		$word_len = strlen($word);

		// find the start positions for the words
		// get the context for words based upon
		if ($count > 1) {
			$str_pos[$word] = array();
			$offset = 0;
			while (FALSE !== $pos = strpos($haystack, $word, $offset)) {
				$str_pos[$word][] = $pos;
				$offset += $pos + $word_len;
			}
		} else {
			$str_pos[$word] = array(strpos($haystack, $word));
		}
		$substr_counts[$word] = $count;
	}

//A test with multiple words and now more in the subject too because words need to be everywhere

	// sort by order of occurence
	krsort($substr_counts);
	$full_count = array_sum($substr_counts);





	// get full number of matches against all words to see how many we actually want to look at.




//	$desc = search_get_relevant_substring($entity->description, $params['query'], '<strong class="searchMatch">', '</strong>');


	$params['query'];
	// "this is"just a test "silly person"

	// check for "s
	$words_quotes = explode('"', $needle);

	$words_orig = explode(' ', $needle);
	$words = array();

	foreach ($words_orig as $i => $word) {
		// figure out if we have a special operand
		$operand = substr($word, 0, 1);
		switch($operand) {
			case '"':
				// find the matching " if any.  else, remove the "
				if (substr_count($query, '"') < 2) {
					$words[] = substr($word, 1);
				} else {
					$word = substr($word, 1);
					$word_i = $i;
					while ('"' != strpos($words_orig[$word_i], '"')) {
						$word .= " {$words_orig[$word_i]}";
						unset($words_orig[$word_i]);
					}


				}

				break;

			case '+':
				// remove +
				$words[] = substr($word, 1);
				break;

			case '~':
			case '-':
				// remove this from highlighted list.

				break;
		}
	}

	// pick out " queries
	if (substr_count($query, '"') >= 2) {

	}

	// ignore queries starting with -


	// @todo figure out a way to "center" the matches within the max_length.
	// if only one match, its context is $context + $max_length / 2
	// if 2 matches, its context is $context + $max_length / 4
	// if 3 matches, its context is $context + $max_length / 6
	// $context per match = $min_match_context + ($max_length / $num_count_match)

	// if $max_length / ($matched_count * 2) < $context
	// only match against the first X matches where $context >= $context
}

/**
 * Returns a matching string with $context amount of context, optionally
 * surrounded by $before and $after.
 *
 * If no match is found, restricts string to $context*2 starting from strpos 0.
 *
 * @param str $haystack
 * @param str $needle
 * @param str $before
 * @param str $after
 * @param int $context
 * @return str
 */
function search_get_relevant_substring($haystack, $needle, $before = '', $after = '', $context = 75) {
	$haystack = strip_tags($haystack);
	$needle = strip_tags($needle);

	$pos = strpos(strtolower($haystack), strtolower($needle));

	if ($pos === FALSE) {
		$str = substr($haystack, 0, $context*2);
		if (strlen($haystack) > $context*2) {
			$str .= '...';
		}

		return $str;
	}

	$start_pos = $pos - $context;

	if ($start_pos < 0) {
		$start_pos = 0;
	}

	// get string from -context to +context
	$matched = substr($haystack, $start_pos, $context*2);

	// add elipses to front.
	if ($start_pos > 0) {
		$matched = "...$matched";
	}

	// add elipses to end.
	if ($pos + strlen($needle) + $context*2 < strlen($haystack)) {
		$matched = "$matched...";
	}

	// surround if needed
	if ($before || $after) {
		$matched = str_ireplace($needle, $before . $needle . $after, $matched);
	}

	return $matched;
}


/**
 * Passes entities, count, and original params to the view functions for
 * search type.
 *
 * @param array $entities
 * @param int $count
 * @param array $params
 * @return string
 */
function search_get_listing_html($entities, $count, $params) {
	if (!is_array($entities) || !$count) {
		return FALSE;
	}

	$view_order = array();

	// check if there's a special search view for this type:subtype
	if (isset($params['type']) && $params['type'] && isset($params['subtype']) && $params['subtype']) {
		$view_order[] = "search/{$params['type']}/{$params['subtype']}/listing";
	}

	// also check for the default type
	if (isset($params['type']) && $params['type']) {
		$view_order[] = "search/{$params['type']}/listing";
	}

	// check search types
	if (isset($params['search_type']) && $params['search_type']) {
		$view_order[] = "search/{$params['search_type']}/listing";
	}

	// finally default to a search listing default
	$view_order[] = "search/listing";

	$vars = array(
		'entities' => $entities,
		'count' => $count,
		'params' => $params
	);

	foreach ($view_order as $view) {
		if (elgg_view_exists($view)) {
			return elgg_view($view, $vars);
		}
	}

	return FALSE;
}

/**
 * Returns a where clause for a search query.
 *
 * @param str $table Prefix for table to search on
 * @param array $fields Fields to match against
 * @param array $params Original search params
 * @return str
 */
function search_get_where_sql($table, $fields, $params) {
	global $CONFIG;
	$query = $params['query'];

	// add the table prefix to the fields
	foreach ($fields as $i => $field) {
		$fields[$i] = "$table.$field";
	}

	// if query is shorter than the min for fts words
	// it's likely a single acronym or similar
	// switch to literal mode
	if (strlen($query) < $CONFIG->search_info['min_chars']) {
		$likes = array();
		foreach ($fields as $field) {
			$likes[] = "$field LIKE '%$query%'";
		}
		$likes_str = implode(' OR ', $likes);
		$where = "($table.guid = e.guid AND	($likes_str))";
	} else {
		// if using advanced or paired "s, switch into boolean mode
		if ((isset($params['advanced_search']) && $params['advanced_search']) || substr_count($query, '"') >= 2 ) {
			$options = 'IN BOOLEAN MODE';
		} else {
			$options = 'IN NATURAL LANGUAGE MODE';
		}

		// if short query, use query expansion.
		if (strlen($query) < 6) {
			$options .= ' WITH QUERY EXPANSION';
		}
		// if query is shorter than the ft_min_word_len switch to literal mode.
		$fields_str = implode(',', $fields);
		$where = "($table.guid = e.guid AND (MATCH ($fields_str) AGAINST ('$query' $options)))";
	}

	return $where;
}

function search_get_query_where_sql($table, $query) {
	// if there are multiple "s or 's it's a literal string.

}

/** Register init system event **/

register_elgg_event_handler('init','system','search_init');