<?php
/*
Plugin Name: WP-DeleteTags - TagAssasin.
Plugin URI: http://www.M-Solutions.co.in/
Description: Delete Tags - Tag Assasin Simple yet powerful plugin to delete excess tags from your blog!
Version: 1.0.1
Author: MSolution
Author URI: http://www.M-Solutions.co.in
*/

define("WPDELT_FILE", __FILE__);

/*
 *
 * This is a FREE script. 
 * I have made it nad used it on many of my own sites.
 * I hope you have fun using it and find it useful as i have.
 * Rule one of using new plugins is keeping backups of your database.
 * And the same applies to this plugin.
 * I would be greatful for bugs if any.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR 
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR 
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE. 
 *
**/

if(!class_exists('wp_deltags')):
class wp_deltags
{
	function __construct()
	{
		if ( basename(dirname(WPDELT_FILE)) == 'plugins' ){
			define( "WPDELT_DIR"			, WP_PLUGIN_DIR.'/'				);
			define( "WPDELT_URL"			, WP_PLUGIN_URL.'/'				);
		} else {
			define( "WPDELT_DIR" 			, WP_PLUGIN_DIR.'/'.basename(dirname(WPDELT_FILE)) . '/');
			define( "WPDELT_URL"			, WP_PLUGIN_URL.'/'.basename(dirname(WPDELT_FILE)) . '/');
		}
		define( "WPDELT_VER"				, "1.0.1" 						);
		add_action( 'admin_menu'			, array( &$this, 'delt_options_page'	));
		add_filter( 'plugin_action_links'		, array( &$this, 'delt_plugin_actions'	), 10, 2 );
	}

	function delt_plugin_actions($links, $file)
	{
		if( strpos( $file, basename(WPDELT_FILE)) !== false )
		{
			$link = '<a href="'.get_option('siteurl').'/wp-admin/options-general.php?page=deltmain">'.__('Settings', 'delt_lang').'</a>';
			array_unshift( $links, $link );
		}
		return $links;
	}

	function delt_footer() {
		$plugin_data = get_plugin_data( WPDELT_FILE );
		printf('%1$s plugin | Version %2$s | by %3$s<br />', $plugin_data['Title'], $plugin_data['Version'], $plugin_data['Author']); 
	}

	function delt_admin_footer() {
		echo '<br/><div id="page_footer" style="text-align:center"><em>';
		self::delt_footer(); 
		echo '</em></div>';
	}

	function delt_options_page()
	{
		add_options_page( 'WP-DeleteTags TagAssassin', 'WP DeleteTags'	, 8, 'deltmain'	, array( &$this, 'delt_main' ) );
	}

	function delt_main()
	{
		if (!current_user_can('manage_options')) wp_die(__('Sorry, but you have no permissions to change settings.'));

		$res = '';
		$taxonomy = 'post_tag';
		$delnum = 0;

		if(isset($_POST['call']) && $_POST['call'] == "deltags")
		{
			check_admin_referer('delt-deltags');

			$deltag 	= $_REQUEST['deltag'];
			if(!empty($deltag))
			{
			foreach($deltag as $id => $val)
			{
				$id = (int) $id;
				$res .= '<!--'.$id.' -->'.$val.', ';
				wp_delete_term( $id, $taxonomy );
				$delnum++;
			}
			}
		}

		$opt = 'mid';
		$term = '';
		$count = 30;

		if( isset( $_REQUEST['msearch'] ) && trim( $_REQUEST['msearch'] ) != '' )
			$term = trim( esc_attr( stripslashes( $_REQUEST['msearch'] ) ) );

		if( isset( $_REQUEST['mopt'] ) && trim( $_REQUEST['mopt'] ) != '' )
			$opt = trim( esc_attr( stripslashes( $_REQUEST['mopt'] ) ) );

		if( isset( $_REQUEST['mcount'] ) && trim( $_REQUEST['mcount'] ) != '' )
			$count = (int) trim( esc_attr( stripslashes( $_REQUEST['mcount'] ) ) );

		$args = array(
				'orderby'=>'count',
				'number' => $count,
				'get' => 'all'
				);

		if( ! empty( $term ) )
		{
			if( $opt == 'mid' )
				$args['search'] = $term;
			else if( $opt == 'start' )
				$args['name__like'] = $term;
			else
			{
				$opt = '';
				$term = '';
			}
		}

		if( ! empty( $term ) && isset( $_REQUEST['mmass'] ) && trim( $_REQUEST['mmass'] ) == 1 )
		{
			check_admin_referer('delt-deltags');

			$n = $args['number'];
			unset( $args['number'] );
			$tags = get_tags( $args );
			foreach ( $tags as $tag ) {
				$res .= '<!--'.$tag->term_id.' -->'.$tag->name.', ';
				wp_delete_term( $tag->term_id, $taxonomy );
				$delnum++;
			}
			$args['number'] = $n;
		}
		?>
		<div class="wrap">
		<h2><?php _e('WP DeleteTags - TagAssassin&trade;', 'delt_lang')?></h2>
		<p><?php _e('Delete those excess Tags', 'delt_lang')?><br/></p>
		<p><strong><?php _e('Always a good idea to take a <u>database backup before deleting anything!</u>.', 'delt_lang')?></strong></p>

<?php
	if( ! empty( $res ) )
	{
		echo '<div id="message" class="updated fade"><p>Deleting Tags: '.$res.'<br/><br/>Deleted <em>'.$delnum.'</em> Tags</p></div>';
	}
?>
		<form name="tagsearch" id="tagsearch" action="" method="post">
		<input type="hidden" name="call" value="search"/>
		<?php wp_nonce_field('delt-search'); ?>
		<table class="form-table widefat" border="0" cellpadding="3" cellspacing="2">
		<thead><tr><th colspan="2">Search Tags and delete</th></tr></thead>
		<tbody>
		<tr><td width="25%">Search:</td><td><input type="text" name="msearch" value="<?php echo $term;?>"/></td></tr>
		<tr><td width="25%">Option:</td><td><select name="mopt">
					<option value="start" <?php selected( $opt, 'start' );?>> Starts with</option>
					<option value="mid" <?php selected( $opt, 'mid' );?>> contains</option></select></td></tr>
		<tr><td width="25%">Results:</td><td><select name="mcount">
				<option value="30" <?php selected( $count, '30' );?>> 30 results</option>
				<option value="60" <?php selected( $count, '60' );?>> 60 results</option>
				<option value="90" <?php selected( $count, '90' );?>> 90 results</option>
				<option value="180" <?php selected( $count, '180' );?>> 180 results</option>
				<option value="360" <?php selected( $count, '360' );?>> 360 results</option>
			</select></td></tr>
		<tr><td width="25%">Mass Delete:</td><td><input type="checkbox" name="mmass" id="mmass" value="1"/>
		<br/><span class="decription">Instead of doing a search Masss delete deletes everything that matches the search without displaying what it is getting deleted.<br/>There is no undo, please use with caution.</span><br/><br/><strong>Please take a database backup before using this option.</strong></td></tr>
		</tbody>
		</table>
		<?php submit_button( 'Search' ); ?>
		</form>
		<br/>

		<form name="tagdel" action="" method="post" id="tagdel">
		<?php wp_nonce_field('delt-search'); ?>
		<input type="hidden" name="call" value="deltags"/>
		<input type="hidden" name="msearch" value="<?php echo $term;?>"/>
		<input type="hidden" name="mopt" value="<?php echo $opt;?>"/>
		<?php wp_nonce_field('delt-deltags'); ?>
		<table class="widefat wide-fat" border="0" cellpadding="3" cellspacing="2" id="deltable">
		<thead><tr><th colspan="3"> <label><input type="checkbox" name="delall" id="delall"/> Check all </label></th></tr></thead>
		<tfoot><tr><th colspan="3"> <label><input type="checkbox" name="delall1" id="delall1"/> Check all </label></th></tr></tfoot>
		<tbody>
		<tr><td colspan="3">Results with [count]</td></tr>
		<tr><td colspan="3"><small><!--Results with: <?php print_r( $args );?> --></small></td></tr>
		<?php
		$i = 1;
		$col = 0;

		$tags = get_tags( $args );
		foreach ( $tags as $tag ) {
			if( $col == 0 )
				$html .= '<tr>';

			$col++;

			$html .= '<td><label> '.$i++.': <input type="checkbox" name="deltag['.$tag->term_id.']" value="'.$tag->name.'"/> '.$tag->name.' [ '.$tag->count.' ] </label></td>';

			if( $col == 3 )
			{
				$col = 0;
				$html .= '</tr>';
			}
		}

		if( $col > 0 )
			$html .= '<td colspan="'.( 3 - $col ) .'"></tr>';

		echo $html;
		?>
		</tbody>
		</table>
		<?php submit_button( 'Delete Tags' ); ?>
		</form>
		</div>
		<script type="text/javascript">
		if( typeof jQuery == "function" ){
		jQuery(document).ready(function($){
			$("#delall").click(function(){
				var checkedStatus = this.checked;
				$("#deltable tbody td input:checkbox").each(function() {
						this.checked = checkedStatus;
				});
			});
			$("#delall1").click(function(){
				var checkedStatus = this.checked;
				$("#deltable tbody td input:checkbox").each(function() {
						this.checked = checkedStatus;
				});
			});

			$("#tagdel").submit( function( event ){
				var rr = confirm('Are you sure you wish to Delete these tags?')
				if( rr == false )
					return false;
			});

			$("#tagsearch").submit( function( event ){
				if( $("#mmass").is(":checked") != false )
				{
					var rr = confirm('You have selected the Mass delete option, ALL tags matching the search term will be deleted without prompt.\nClick cancel to stop. There is no undo');
					if( rr == false )
						return false;
				}
			});

			$("#mmass").click( function(){
				var checkedStatus = this.checked;
				if( checkedStatus == true )
					alert("Selecting this option will delete all tags matching this criterea,\n You will NOT see the tags before they are deleted.\n\nThere is no undo");
			});
		});
		}
		</script>
		<?php
		$this->delt_admin_footer();
	}

}
endif;

global $wp_deltags;
if(empty($wp_deltags)) $wpdt = & new wp_deltags();