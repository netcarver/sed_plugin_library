<?php

$plugin['revision'] = '$LastChangedRevision$';

$revision = @$plugin['revision'];
if( !empty( $revision ) )
	{
	$parts = explode( ' ' , trim( $revision , '$' ) );
	$revision = $parts[1];
	if( !empty( $revision ) )
		$revision = '.' . $revision;
	}

$plugin['name'] = 'sed_plugin_library';
$plugin['version'] = '0.3' . $revision;
$plugin['author'] = 'Netcarver';
$plugin['author_uri'] = 'http://txp-plugins.netcarving.com';
$plugin['description'] = 'Helper functions for sed plugins.';
$plugin['type'] = 2;

@include_once('../zem_tpl.php');

if (0) {
?>
<!-- CSS
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
div#sed_help td { vertical-align:top; }
div#sed_help code { font-weight:bold; font: 105%/130% "Courier New", courier, monospace; background-color: #FFFFCC;}
div#sed_help code.sed_code_tag { font-weight:normal; border:1px dotted #999; background-color: #f0e68c; display:block; margin:10px 10px 20px; padding:10px; }
div#sed_help a:link, div#sed_help a:visited { color: blue; text-decoration: none; border-bottom: 1px solid blue; padding-bottom:1px;}
div#sed_help a:hover, div#sed_help a:active { color: blue; text-decoration: none; border-bottom: 2px solid blue; padding-bottom:1px;}
div#sed_help h1 { color: #369; font: 20px Georgia, sans-serif; margin: 0; text-align: center; }
div#sed_help h2 { border-bottom: 1px solid black; padding:10px 0 0; color: #369; font: 17px Georgia, sans-serif; }
div#sed_help h3 { color: #693; font: bold 12px Arial, sans-serif; letter-spacing: 1px; margin: 10px 0 0;text-transform: uppercase;}
</style>
# --- END PLUGIN CSS ---
-->
<!-- HELP
# --- BEGIN PLUGIN HELP ---
<div id="sed_help">

h1(#intro). Plugin Library

sed_plugin_library v0.3 (June 18th, 2006)

Provides some useful helper functions for plugins.

h2(#functions). Function Listing

|_. Function                         |_. Description |
| @sed_lib_extract_name_value_pairs@        | Returns an array of name value pairs from the _variable-list_ it is given. |
| @sed_lib_extract_packed_vars@             | Returns an array of key->value mappings parsed from all the sections of a _packed-string_. |
| @sed_lib_extract_packed_variable_section@ | Returns an array of key->value mappings parsed from one section of a _packed-string_. |
| @sed_lib_print_keys@                      | Echo's the keys of a given array without the values being shown. |
| @sed_lib_print_vals@                      | Echo's the values of a given array without the keys being shown. |
| @sed_lib_txp_version@                     | Txp tag that outputs the current installation's version |

h2(#formats). Formats

*packed string* :: _section_ ['|' _section_ ]
*section*       :: section-name '(' _variable-list_ ')'
*variable-list* :: name='value' [ ';' variable-list ]

h3(#examples). Examples

Here is a valid variable-list...

a='1' ; b='2' ; Hello='Goodbye'

Here is a valid packed-string...

copyright(owner='Steve';start='1970')|location(state='sabah')|personal(dob='1/1/01';email='';phone='')

Notice that a packed string includes one or more variable lists. Each list is wrapped in parenthasis, given a prefixed name and separated from the next section by the '|'
 character.

h2(#versions). Version History

v0.3

* Renamed functions.
* Changed help files.

v0.2

* Pulled out the variable list parsing code into a common function @_extract_name_value_pairs@.

v0.1

* Functions to extract packed variables from strings.

</div>
# --- END PLUGIN HELP ---
-->
<?php
}

# --- BEGIN PLUGIN CODE ---

/* parses a string for a name='value' list */
function sed_lib_extract_name_value_pairs( $content , $prefix='', $section_name='' , $attach_name=false, $variable_delim_char=';', $sep_char='_' )
	{
	$result = array();

	$content = trim( $content );
	if( empty( $content ) )
		return $result;

	$chunks = explode( $variable_delim_char , $content );
	//
	//	Build the result array mapping
	//  [ variable_x => value_x ]
	//
	if( 0 == count( $chunks ) )
		return $result;

	foreach( $chunks as $chunk )
		{
		$chunk = trim( $chunk );
		if( empty( $chunk ) )
			continue;

		list( $storage_key, $value ) = explode( '=', $chunk );

		$storage_key = trim($storage_key);
		if( empty( $storage_key ) )
			continue;

		if( $attach_name and !empty($section_name) )
			$storage_key = trim($section_name).$sep_char.$storage_key;
		if( !empty( $prefix) )
			$storage_key = trim($prefix).$sep_char.$storage_key;

		$result[ $storage_key ] = trim( $value, " '\"" );
		}
	return $result;
	}

/*	Returns an array of key->value mappings parsed from all the sections of a packed string. */
function sed_lib_extract_packed_vars( $packed_string, $prefix='', $attach_name=true, $section_char='|', $variable_delim_char=';' ) {
	$result = array();

	if( empty( $packed_string ) )
		return false;
	//
	//	Break the packed string on the section boundaries...
	//
	$sections = explode( $section_char , $packed_string );
	$count = count( $sections );
	if( 0 == $count )
		return false;

	foreach( $sections as $section )
		{
		//
		//	Pull out the section name
		//
		$section_len = strlen( $section );
		$len = strpos( $section , '(' );
		$section_name = substr( $section , 0 , $len );
		$content = substr( $section , $len + 1 , ($section_len - $len - 2) );
		$result = sed_lib_extract_name_value_pairs( $content, $prefix, $section_name, $attach_name, $variable_delim_char );
		}
	return $result;
	}

/*	Returns an array of key->value mappings parsed from one section of a packed string. */
function sed_lib_extract_packed_variable_section( $section_name, $packed_string, $prefix='', $attach_name=false, $section_char='|', $variable_delim_char=';' ) {
	$result = array();

	if( empty( $packed_string ) or empty( $section_name ) )
		return false;
	//
	//	Break the packed string on the section boundaries...
	//
	$sections = explode( $section_char , $packed_string );
	$count = count( $sections );
	if( 0 == $count )
		return false;
	//
	//	Find the section with the matching prefix. If it is not present
	// then return false.
	//
	$found = false;
	$section = '';
	$len = strlen( $section_name );
	for( $i = 0; $i < $count; $i++ ) {
		$s = $sections[$i];
		if( $s{$len} === '(' )
			{
			if( substr( $s , 0 , $len ) == $section_name )
				{
				$section = $s;
				$i = $count;
				}
			}
		}
	if( '' === $section )
		{
		return false;
		}
	//
	//	Split this section on the variable delimiter...
	//
	$section_len = strlen( $section );
	$content = substr( $section , $len + 1 , $section_len - $len - 2 );
	$result = sed_lib_extract_name_value_pairs( $content, $prefix, $section_name, $attach_name, $variable_delim_char );
	return $result;
	}

/* Array print keys: echo's the keys from a given array in a more compact format than print_r */
function sed_lib_print_keys( $input, $postfix = '', $columnated = false ) {
	if( !is_array( $input ) )
		return;

	echo( 'Array'.(($columnated)? br : '').'( '.(($columnated)? br : '') );
	foreach( $input as $k=>$v )
		echo( "[$k] ".(($columnated)? br : '') );
	echo( ')'.$postfix );
	}

/* Array print values: echo's the values from a given array in a more compact format than print_r */
function sed_lib_print_vals( $input, $postfix = '', $columnated = false ) {
	if( !is_array( $input ) )
		return;

	echo( 'Array'.(($columnated)? br : '').'( '.(($columnated)? br : '') );
	foreach( $input as $k=>$v )
		echo( "[$v] ".(($columnated) ? br : '') );
	echo( ')'.$postfix );
	}

/* Intended as a tag to output the current txp version */
function sed_lib_txp_version()
	{
	global $prefs;
	echo $prefs['version'];
	}

# --- END PLUGIN CODE ---

?>
