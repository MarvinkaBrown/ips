<?php
namespace IPS\Theme;
class class_core_admin_dashboard extends \IPS\Theme\Template
{	function adminnotes( $lastUpdated, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $formClass='', $attributes=array(), $sidebar=array(), $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-controller='core.admin.dashboard.adminNotes' class="i-padding_3">
	<form id='admin_notes' accept-charset='utf-8' action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-ipsForm class="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $formClass, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
>
		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
		
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

foreach ( $elements as $collection ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

				{$input->html()}
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


		<div class='i-flex i-color_soft i-margin-top_2'>
			<div class='i-flex_11'>
				<span data-role='notesLoading' class='ipsHide'>
					<i class='ipsLoadingIcon'></i> 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'saving', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</span>	
				<h3 class='ipsTitle ipsTitle--h5'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'admin_notes_last_update', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
				<span data-role='notesInfo'>
					
IPSCONTENT;

if ( $lastUpdated ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $lastUpdated, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'never', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</span>
			</div>
			<button class='ipsButton ipsButton--inherit'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'save', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function backgroundQueue( $rows, $totalCount ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $rows ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<div class="ipsData ipsData--table ipsData--background-queue">
			
IPSCONTENT;

foreach ( $rows as $row ):
$return .= <<<IPSCONTENT

				<div class='ipsData__item'>
					<div class='i-basis_200'>
						
IPSCONTENT;

if ( $row['complete'] === NULL OR $row['complete'] > 100 ):
$return .= <<<IPSCONTENT

							<div class="ipsProgress ipsProgress--animated ipsProgress--indeterminate"><div class="ipsProgress__progress">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'progress_bar_percent_not_available', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div></div>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<progress class="ipsProgress" title='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $row['complete'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%' data-ipstooltip value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( number_format( $row['complete'], 2), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" max="100"></progress>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsData__title'>
						<span class='i-font-size_1 i-margin-bottom_1 ipsTruncate ipsTruncate_line'><strong>{$row['text']}</strong></span>
					</div>
				</div>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</div>
	</i-data>
	
IPSCONTENT;

if ( $totalCount > 100 ):
$return .= <<<IPSCONTENT

		<p>
			
IPSCONTENT;

$pluralize = array( $totalCount - 100 ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'background_process_more', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		</p>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<p class="i-color_soft i-text-align_center">
		
IPSCONTENT;

if ( \IPS\Settings::i()->task_use_cron == 'normal' AND !\IPS\CIC ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'background_processes_desc_nocron', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'background_processes_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !\IPS\CIC ):
$return .= <<<IPSCONTENT

			<br>
			
IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack('background_process_run_title')); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'background_processes_run_now', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</p>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class="ipsEmptyMessage">
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_background_processes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function connectFailures( $failures, $failureCount=0 ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$pluralize = array( $failureCount ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'connect_failures_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
<br><br>

IPSCONTENT;

if ( !empty( $failures ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ul class="ipsData ipsData--table ipsData--compact ipsData--connect-failures">
		
IPSCONTENT;

foreach ( $failures as $failure  ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item'>
				<div class='ipsData__main'>
					<h4 class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $failure['slave']['slave_url'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
					<p class='ipsData__meta'>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $failure['count'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'failed_logins_suffix', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</p>
				</div>
				<div>
					<div class='ipsTree_controls'>
						<ul data-ipscontrolstrip class="ipsControlStrip">
							<li class="ipsControlStrip_button">
								<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=settings&controller=login&do=retryConnect&slave={$failure['slave']['slave_id']}", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'slave_try_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip>
									<i class='ipsControlStrip_icon fa-solid fa-circle-play'></i>
									<span class='ipsControlStrip_item'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'slave_try_again', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</a>
							</li>
							<li class="ipsControlStrip_button">
								<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=dashboard&deleteSlave={$failure['slave']['slave_id']}" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_slave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-confirm data-ipsTooltip data-confirmMessage="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete_slave_confirm', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
									<i class='ipsControlStrip_icon fa-solid fa-xmark-circle'></i>
									<span class='ipsControlStrip_item'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'remove_slave', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span>
								</a>
							</li>
						</ul>
					</div>
				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function dashboard( $cols, $blocks, $info ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div data-controller='core.admin.dashboard.main'>	
	<div class='ipsSpanGrid'>
		<div class='ipsSpanGrid__8' data-role="mainerColumn">
			<ol data-role="mainColumn">
				
IPSCONTENT;

foreach ( $cols['main'] as $cellKey ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( array_key_exists( $cellKey, $blocks ) and isset($info[ $cellKey ]) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "dashboard", "core" )->widgetWrapper( $blocks[ $cellKey ], $info[ $cellKey ], $cols['collapsed'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
		</div>
		<div class='ipsSpanGrid__4 acpWidget_sidebar'>
			<ol data-role="sideColumn">
				
IPSCONTENT;

foreach ( $cols['side'] as $cellKey ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( array_key_exists( $cellKey, $blocks ) and isset($info[ $cellKey ]) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "dashboard", "core" )->widgetWrapper( $blocks[ $cellKey ], $info[ $cellKey ], $cols['collapsed'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ol>
		</div>
	</div>
</div>
IPSCONTENT;

		return $return;
}

	function dashboardHeader( $info, $blocks ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div id='acpPageHeader' class='cDashboardHeader acpPageHeader_flex'>
	<div>
		<h1 class='ipsTitle ipsTitle--h3'>
			
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Output::i()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\IN_DEV ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->searchKeywords( 'app=' . \IPS\Request::i()->app . '&module=' . \IPS\Request::i()->module . '&controller=' . \IPS\Request::i()->controller . ( ( isset( \IPS\Request::i()->do ) and \IPS\Request::i()->do != 'do' ) ? ( '&do=' . \IPS\Request::i()->do ) : '' ) . ( ( \IPS\Request::i()->controller == 'enhancements' and ( isset( \IPS\Request::i()->id ) ) ) ? ( '&id=' . \IPS\Request::i()->id ) : '' ) );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</h1>
		<p>
			Invision Community 
IPSCONTENT;

$sprintf = array(\IPS\Application::load('core')->version); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'acp_version_number', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) ) . ' | Nulled by NullForums.net';
$return .= <<<IPSCONTENT

		</p>
	</div>

	
IPSCONTENT;

$unusedBlocks = 0;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $info as $block ):
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !isset($blocks[ $block['key'] ])  ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$unusedBlocks++;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


	<div class='acpToolbar'>
		
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'admin' )->pageButtons( array( 'add' => array( 'primary' => true, 'icon' => 'plus', 'title' => 'add_button', 'link' => \IPS\Http\Url::internal( '#elAddWidgets_button_menu' ), 'id' => 'elAddWidgets', 'dropdown' => true, 'class' => ( $unusedBlocks > 0 ? '' : 'ipsButton--disabled' ), 'data' => array( 'ipsMenu' => true )  ) ) );
$return .= <<<IPSCONTENT

	</div>
</div>
<ul id='elAddWidgets_menu' class='ipsMenu ipsMenu_auto ipsHide'>
	
IPSCONTENT;

foreach ( $info as $block ):
$return .= <<<IPSCONTENT

		<li class='ipsMenu_item 
IPSCONTENT;

if ( isset($blocks[ $block['key'] ])  ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block['key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-widgetName='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
			<a href='#'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $block['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
		</li>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

</ul>
IPSCONTENT;

		return $return;
}

	function failedLogins( $logins ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( \count( $logins ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ul class="ipsData ipsData--table ipsData--compact ipsData--failed-logins">
		
IPSCONTENT;

foreach ( $logins as $login  ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item'>
				<div class='ipsData__main'>
					<h4 class='ipsData__title'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $login['admin_username'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
					<p class='ipsData__meta'>
						
IPSCONTENT;

$val = ( $login['admin_time'] instanceof \IPS\DateTime ) ? $login['admin_time'] : \IPS\DateTime::ts( $login['admin_time'] );$return .= $val->html();
$return .= <<<IPSCONTENT

					</p>
				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>
	<div class="i-padding_3">
		<a href="
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=staff&controller=admin&do=loginLogs&filter=adminloginlogs_unsuccessful", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_failed_logins_all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	<div class='ipsEmptyMessage'>
		
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_failed_logins', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function filesystemNotCic( $classname ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $classname, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

<div class='ipsMessage ipsMessage--warning i-margin-top_1'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'file_storage_cic_filesystem', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

		return $return;
}

	function fileTable( $table, $headers, $rows, $quickSearch ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div data-baseurl="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-resort='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->resortKey, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-controller="core.global.core.table( file.moderate ),core.front.core.moderation">
	
IPSCONTENT;

if ( isset( $headers['_buttons'] ) ):
$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->buttons( $table->rootButtons, '' );
endif;
$return .= <<<IPSCONTENT

	<div class="ipsBox">
		
IPSCONTENT;

if ( $quickSearch !== NULL or $table->advancedSearch or !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

			<div data-role="tableSortBar">
				<div class='ipsButtonBar ipsButtonBar--top'>
					<div data-role="tablePagination" class='ipsButtonBar__pagination 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

					</div>
					<div class='ipsButtonBar__end'>
						<ul class='ipsDataFilters'>
							
IPSCONTENT;

if ( !empty( $table->filters ) ):
$return .= <<<IPSCONTENT

								<li>
									<a href='#elFilterMenu_menu' class='ipsDataFilters__button' data-role="tableFilterMenu" id='elFilterMenu' data-ipsMenu data-ipsMenu-activeClass='ipsDataFilters__button--active' data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'filter', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
									<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elFilterMenu_menu'>
										<li data-ipsMenuValue='' class='ipsMenu_item 
IPSCONTENT;

if ( !array_key_exists( $table->filter, $table->filters ) ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'><a href=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

foreach ( $table->filters as $k => $q ):
$return .= <<<IPSCONTENT

											<li class='ipsMenu_item 
IPSCONTENT;

if ( $k === $table->filter ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-action="tableFilter" data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><a href=
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $k, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) )->setPage( 'page', 1 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$val = "{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
										
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

									</ul>
								</li>
							
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							<li>
								<a href='#elSortMenu_menu' class='ipsDataFilters__button' id='elSortMenu' data-ipsMenu data-ipsMenu-activeClass='ipsDataFilters__button--active' data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'sort_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elSortMenu_menu'>
									
IPSCONTENT;

foreach ( $headers as $k => $header ):
$return .= <<<IPSCONTENT

										
IPSCONTENT;

if ( $header !== '_buttons' && !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT

											<li class='ipsMenu_item 
IPSCONTENT;

if ( $header == $table->sortBy ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												
IPSCONTENT;

if ( $header == $table->sortBy and $table->sortDirection == 'desc' ):
$return .= <<<IPSCONTENT

													<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'desc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

													<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'asc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
												
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

												
IPSCONTENT;

$val = "{$table->langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

												</a>
											</li>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							</li>
							<li>
								<a href='#elOrderMenu_menu' class='ipsDataFilters__button' id='elOrderMenu' data-ipsMenu data-ipsMenu-activeClass='ipsDataFilters__button--active' data-ipsMenu-selectable="radio">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'order_by', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
 <i class='fa-solid fa-caret-down'></i></a>
								<ul class='ipsMenu ipsMenu_auto ipsMenu_withStem ipsMenu_selectable ipsHide' id='elOrderMenu_menu'>
									<li class='ipsMenu_item 
IPSCONTENT;

if ( $table->sortDirection == 'asc' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='asc'>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => 'asc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ascending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
									<li class='ipsMenu_item 
IPSCONTENT;

if ( $table->sortDirection == 'desc' ):
$return .= <<<IPSCONTENT
ipsMenu_itemChecked
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-ipsMenuValue='desc'>
										<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => 'desc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'descending', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
									</li>
								</ul>
							</li>
							<li>
								<a class="ipsDataFilters__button ipsJS_show" href="#elCheck_menu" id="elCheck" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows_tooltip', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' data-ipsTooltip data-ipsAutoCheck data-ipsAutoCheck-context="#elFileResults" data-ipsMenu data-ipsMenu-activeClass="ipsDataFilters__button--active">
									<span class="cAutoCheckIcon"><i class="fa-regular fa-square"></i></span> <i class="fa-solid fa-caret-down"></i>
									<span class='ipsNotification' data-role='autoCheckCount'>0</span>
								</a>
								<ul class="ipsMenu ipsMenu_auto ipsMenu_withStem ipsHide" id="elCheck_menu">
									<li class="ipsMenu_title">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'select_rows', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</li>
									<li class="ipsMenu_item" data-ipsMenuValue="all"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'all', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
									<li class="ipsMenu_item" data-ipsMenuValue="none"><a href="#">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'none', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a></li>
								</ul>
							</li>
						</ul>
						<!-- Search -->
						
IPSCONTENT;

if ( $quickSearch !== NULL or $table->advancedSearch ):
$return .= <<<IPSCONTENT

							<div class="acpTable_search">
								
IPSCONTENT;

if ( $quickSearch !== NULL ):
$return .= <<<IPSCONTENT

									<input type='text' data-role='tableSearch' results placeholder="
IPSCONTENT;

if ( \is_string( $quickSearch ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$sprintf = array(\IPS\Member::loggedIn()->language()->addToStack( $table->langPrefix . $quickSearch )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search_prefix', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\Widget\Request::i()->quicksearch?:'', ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $table->advancedSearch ):
$return .= <<<IPSCONTENT

									<a class='acpWidgetSearch' data-ipsTooltip aria-label='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'advancedSearchForm' => '1', 'filter' => $table->filter, 'sortby' => $table->sortBy, 'sortdirection' => $table->sortDirection ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-ipsDialog data-ipsDialog-title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'search', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-gear'></i></a>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</div>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
				</div>
			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		<div data-role="extraHtml">{$table->extraHtml}</div>
		<form action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->csrf()->setQueryString('do','multimod'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" data-role='moderationTools' data-ipsPageAction>
			<div class="ipsTableScroll">
				<table class='ipsTable ipsTable_zebra 
IPSCONTENT;

foreach ( $table->classes as $class ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
' data-role="table" data-ipsKeyNav data-ipsKeyNav-observe='e d return'>
					<thead>
						<tr class='i-background_3'>
							
IPSCONTENT;

foreach ( $headers as $k => $header ):
$return .= <<<IPSCONTENT

								
IPSCONTENT;

if ( $header != 'attach_id' ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( $header !== '_buttons' ):
$return .= <<<IPSCONTENT

										<th class='
										
IPSCONTENT;

if ( !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT
ipsTable_sortable 
IPSCONTENT;

if ( $table->sortBy AND $header == ( mb_strrpos( $table->sortBy, ',' ) !== FALSE ? trim( mb_substr( $table->sortBy, mb_strrpos( $table->sortBy, ',' ) + 1 ) ) : $table->sortBy ) ):
$return .= <<<IPSCONTENT
ipsTable_sortableActive ipsTable_sortable
IPSCONTENT;

if ( $table->sortDirection == 'asc' ):
$return .= <<<IPSCONTENT
Asc
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
Desc
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
ipsTable_sortableAsc
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( array_key_exists( $header, $table->classes ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->classes[ $header ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-key="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $header, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT
data-action="tableSort" 
IPSCONTENT;

if ( $table->sortBy AND $header == ( mb_strrpos( $table->sortBy, ',' ) !== FALSE ? trim( mb_substr( $table->sortBy, mb_strrpos( $table->sortBy, ',' ) + 1 ) ) : $table->sortBy ) ):
$return .= <<<IPSCONTENT
aria-sort="
IPSCONTENT;

if ( $table->sortDirection == 'asc' ):
$return .= <<<IPSCONTENT
ascending
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
descending
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( isset( $table->widths[ $header ] ) ):
$return .= <<<IPSCONTENT
style="width: 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->widths[ $header ], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
%"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
										
IPSCONTENT;

if ( !\in_array( $header, $table->noSort ) ):
$return .= <<<IPSCONTENT

											
IPSCONTENT;

if ( $header == $table->sortBy and $table->sortDirection == 'desc' ):
$return .= <<<IPSCONTENT

												<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'asc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
											
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

												<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->baseUrl->setQueryString( array( 'filter' => $table->filter, 'sortby' => $header, 'sortdirection' => 'desc' ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
											
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$val = "{$table->langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

											<i class="fa-solid fa-caret-down ipsTable_sortable__icon"></i>
											</a>
										
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

											
IPSCONTENT;

$val = "{$table->langPrefix}{$header}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

										</th>
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										<th>&nbsp;</th>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							<th>&nbsp;</th>
						</tr>
					</thead>
					<tbody data-role="tableRows" id="elFileResults" data-controller='ips.admin.files.multimod'>
						
IPSCONTENT;

$return .= $table->rowsTemplate[0]->{$table->rowsTemplate[1]}( $table, $headers, $rows );
$return .= <<<IPSCONTENT

					</tbody>
					<tfoot class="i-background_3 ipsJS_hide" data-role="pageActionOptions">
						<tr>
							<td colspan="8">
								<div>
									<select class="ipsInput ipsInput--select" name="modaction" data-role="moderationAction">
										
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'overview', 'files_delete') ):
$return .= <<<IPSCONTENT

											<option value="delete" data-icon="trash">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'delete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</option>
										
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

									</select>
									<button type="submit" class="ipsButton ipsButton--secondary ipsButton--tiny">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'submit', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
								</div>
							</td>
						</tr>
					</tfoot>
				</table>
			</div>
		</form>
		<div class='ipsButtonBar ipsButtonBar--bottom'>
			<div data-role="tablePagination" class='ipsButtonBar__pagination 
IPSCONTENT;

if ( $table->pages <= 1 ):
$return .= <<<IPSCONTENT
ipsHide
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'>
				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core", 'global' )->pagination( $table->baseUrl, $table->pages, $table->page, $table->limit );
$return .= <<<IPSCONTENT

			</div>
		</div>
	</div>
</div>

IPSCONTENT;

		return $return;
}

	function fileTableRows( $table, $headers, $rows ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( empty( $rows ) ):
$return .= <<<IPSCONTENT

	<tr>
		<td colspan="
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \count( $headers ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			<div class='i-padding_4 i-color_soft'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( isset( $table->rootButtons['add'] ) ):
$return .= <<<IPSCONTENT

				&nbsp;&nbsp;
				<a
					
IPSCONTENT;

if ( isset( $table->rootButtons['add']['link'] ) ):
$return .= <<<IPSCONTENT
href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->rootButtons['add']['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					title='
IPSCONTENT;

$val = "{$table->rootButtons['add']['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'
					class='ipsButton ipsButton--secondary ipsButton--small 
IPSCONTENT;

if ( isset( $table->rootButtons['add']['class'] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->rootButtons['add']['class'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'
					role="button"
					
IPSCONTENT;

if ( isset( $table->rootButtons['add']['data'] ) ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

foreach ( $table->rootButtons['add']['data'] as $k => $v ):
$return .= <<<IPSCONTENT

							data-
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
						
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( isset( $table->rootButtons['add']['hotkey'] ) ):
$return .= <<<IPSCONTENT

						data-keyAction='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $table->rootButtons['add']['hotkey'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					>
IPSCONTENT;

$val = "{$table->rootButtons['add']['title']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</td>
	</tr>

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

	
IPSCONTENT;

foreach ( $rows as $r ):
$return .= <<<IPSCONTENT

		<tr data-keyNavBlock 
IPSCONTENT;

if ( isset( $r['_buttons']['view'] ) ):
$return .= <<<IPSCONTENT
data-tableClickTarget="view"
IPSCONTENT;

elseif ( isset( $r['_buttons']['edit'] ) ):
$return .= <<<IPSCONTENT
data-tableClickTarget="edit"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
			
IPSCONTENT;

foreach ( $r as $k => $v ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $k != 'attach_id' ):
$return .= <<<IPSCONTENT

					<td class='
IPSCONTENT;

if ( $k === ( $table->mainColumn ?: $table->quickSearch ) ):
$return .= <<<IPSCONTENT
ipsTable_wrap
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $k === $table->mainColumn ):
$return .= <<<IPSCONTENT
ipsTable_primary
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $k === '_buttons' ):
$return .= <<<IPSCONTENT
ipsTable_controls
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( isset( $table->rowClasses[ $k ] ) ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( implode( ' ', $table->rowClasses[ $k ] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' 
IPSCONTENT;

if ( $k !== $table->mainColumn && $k !== '_buttons' && $k !== 'photo' ):
$return .= <<<IPSCONTENT
data-title="
IPSCONTENT;

$val = "{$table->langPrefix}{$k}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;

if ( $k === '_buttons' ):
$return .= <<<IPSCONTENT

							
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->controlStrip( $v );
$return .= <<<IPSCONTENT

						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							{$v}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</td>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \IPS\Member::loggedIn()->hasAcpRestriction( 'core', 'overview', 'files_delete' ) ):
$return .= <<<IPSCONTENT

				<td class='cFilesTable_multimod'>
					<input type="checkbox" name="multimod[
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $r['attach_id'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
]" class="ipsInput ipsInput--toggle" value="1" data-role="moderation" data-actions="delete" data-state>
				</td>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</tr>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function ipsNews( $news ) {
		$return = '';
		$return .= <<<IPSCONTENT

<i-data>
	<ul class="ipsData ipsData--table ipsData--ips-news">
		
IPSCONTENT;

if ( isset($news)  ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $news as $article ):
$return .= <<<IPSCONTENT

				<li class="ipsData__item">
					<span class='i-basis_120 i-color_soft i-text-align_end'>
						
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \IPS\DateTime::ts( strtotime( $article['date'] ) )->relative(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</span>
					<span class="ipsData__main">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $article['link'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" target="_blank" rel="noopener">
							
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $article['title'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

						</a>
					</span>
				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'ipsnews_error_generic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</ul>
</i-data>
IPSCONTENT;

		return $return;
}

	function memberStats( $stats, $chart ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-text-align_center i-color_soft i-padding_3'>
	
IPSCONTENT;

$sprintf = array($stats['member_count']); $pluralize = array( $stats['member_count'] ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'memberStatsDashboard_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

</div>
<div>{$chart}</div>

IPSCONTENT;

		return $return;
}

	function noConnectFailures(  ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class='i-font-size_1 i-color_soft i-text-align_center'>
	
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_failed_connect_requests', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function noOnlineUsers(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='i-font-size_2 ipsEmpty'>
	<i class='fa-solid fa-user-group'></i>
	<br>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_online_users', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

</div>

IPSCONTENT;

		return $return;
}

	function onboard(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox i-padding_4 cOnboardBox cOnboardBox--nextSteps'>
	<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_complete', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<p class='cOnboardBox__text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_steps_subtitle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<hr class='ipsHr'>

	<p class='cOnboardBox__text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_steps_intro', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
	<p class='cOnboardBox__text'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_steps_intro2', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

	<hr class='ipsHr'>


	
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'forums' ) ):
$return .= <<<IPSCONTENT

		<div class='ipsBox i-margin-bottom_3 i-flex i-gap_3 i-padding_4'>
			<div class='cOnboardBox__stepIcon i-flex_00 i-text-align_center'>
				<i class='fa-solid fa-comments fa-lg'></i>
			</div>
			<div class='i-flex i-gap_3 i-flex_11 i-flex-wrap_wrap i-basis_400'>
				<div class='cOnboardBox__stepText i-flex_11'>
					<h2 class='cOnboardBox__stepTitle i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_forums_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_forums', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
				<div class='i-flex_00'>
					<a class="ipsButton ipsButton--inherit ipsButton--small" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=forums&module=forums&controller=forums", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_takeme', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


	<div class='ipsBox i-margin-bottom_3 i-flex i-gap_3 i-padding_4'>
			<div class='cOnboardBox__stepIcon i-flex_00 i-text-align_center'>
			<i class='fa-solid fa-user-group fa-lg'></i>
		</div>
		<div class='i-flex i-gap_3 i-flex_11 i-flex-wrap_wrap i-basis_400'>
			<div class='cOnboardBox__stepText i-flex_11'>
				<h2 class='cOnboardBox__stepTitle i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_groups_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_groups', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<div class='i-flex_00'>
				<a class="ipsButton ipsButton--inherit ipsButton--small" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=members&controller=groups", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_takeme', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>

	<div class='ipsBox i-margin-bottom_3 i-flex i-gap_3 i-padding_4'>
			<div class='cOnboardBox__stepIcon i-flex_00 i-text-align_center'>
			<i class='fa-solid fa-unlock fa-lg'></i>
		</div>
		<div class='i-flex i-gap_3 i-flex_11 i-flex-wrap_wrap i-basis_400'>
			<div class='cOnboardBox__stepText i-flex_11'>
				<h2 class='cOnboardBox__stepTitle i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_staff_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_staff', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<div class='i-flex_00'>
				<a class="ipsButton ipsButton--inherit ipsButton--small sm:i-margin-bottom_2" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=staff&controller=admin", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_takeme_admins', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				<a class="ipsButton ipsButton--inherit ipsButton--small" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=staff&controller=moderators", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_takeme_mods', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>

	<div class='ipsBox i-margin-bottom_3 i-flex i-gap_3 i-padding_4'>
			<div class='cOnboardBox__stepIcon i-flex_00 i-text-align_center'>
			<i class='fa-solid fa-paintbrush fa-lg'></i>
		</div>
		<div class='i-flex i-gap_3 i-flex_11 i-flex-wrap_wrap i-basis_400'>
			<div class='cOnboardBox__stepText i-flex_11'>
				<h2 class='cOnboardBox__stepTitle i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_themes_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
				<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_themes', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
			</div>
			<div class='i-flex_00'>
				<a class="ipsButton ipsButton--inherit ipsButton--small" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=customization&controller=themes", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_takeme', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
			</div>
		</div>
	</div>

	
IPSCONTENT;

if ( \IPS\Application::appIsEnabled( 'nexus' ) ):
$return .= <<<IPSCONTENT


		<div class='ipsBox i-margin-bottom_3 i-flex i-gap_3 i-padding_4'>
			<div class='cOnboardBox__stepIcon i-flex_00 i-text-align_center'>
				<i class='fa-solid fa-dollar-sign fa-lg'></i>
			</div>
			<div class='i-flex i-gap_3 i-flex_11 i-flex-wrap_wrap i-basis_400'>
				<div class='cOnboardBox__stepText i-flex_11'>
					<h2 class='cOnboardBox__stepTitle i-margin-bottom_2'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_nexus_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h2>
					<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_next_nexus', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
				</div>
				<div class='i-flex_00 ipsButtons'>
					<a class="ipsButton ipsButton--inherit ipsButton--small" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=subscriptions&controller=subscriptions", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_takeme_subs', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					<a class="ipsButton ipsButton--inherit ipsButton--small" href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=nexus&module=store&controller=packages", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_takeme_packages', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
				</div>
			</div>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function onboardForm( $id, $action, $tabs, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL, $errorTabs=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='cOnboard' data-controller='core.admin.dashboard.onboard'>
	<form accept-charset='utf-8' data-formId="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $action, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-ipsForm class="ipsFormWrap ipsFormWrap--onboard" 
IPSCONTENT;

foreach ( $attributes as $k => $v ):
$return .= <<<IPSCONTENT

IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT
>
		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_activeTab" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->activeTab, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $id, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
_submitted" value="1">
		
IPSCONTENT;

foreach ( $hiddenValues as $k => $v ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( \is_array($v) ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $v as $_v ):
$return .= <<<IPSCONTENT

					<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
[]" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $_v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<input type="hidden" name="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT

			<input type="hidden" name="MAX_FILE_SIZE" value="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $uploadField, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( $form->error ):
$return .= <<<IPSCONTENT

			<div class="ipsMessage ipsMessage--error">
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->error, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</div>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

if ( !empty( $errorTabs ) ):
$return .= <<<IPSCONTENT

			<p class="ipsMessage ipsMessage--error ipsJS_show">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'tab_error', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


		
IPSCONTENT;

$count = 1;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

foreach ( $tabs as $name => $collection ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

if ( $name ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$closed = TRUE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $count == 1 ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$closed = FALSE;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class='ipsBox i-margin-bottom_3 cOnboard__section 
IPSCONTENT;

if ( $closed ):
$return .= <<<IPSCONTENT
cOnboard__section--closed
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-role="sectionWrap">
					<h2 class='ipsTitle ipsTitle--h3 i-padding_3 cOnboard__sectionTitle' data-role="sectionToggle">
						<i class="fa-solid fa-angle-down i-margin-end_icon cOnboard__sectionIcon" data-role="closeSection"></i>
						
IPSCONTENT;

if ( \in_array( $name, $errorTabs ) ):
$return .= <<<IPSCONTENT
<i class="fa-solid fa-circle-exclamation"></i> 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = "{$name}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

					</h2>

					<div class="ipsColumns ipsColumns--lines cOnboard__sectionContents" aria-hidden="false" data-role="sectionForm">
						
IPSCONTENT;

$key = str_replace( 'onboard_tab_', '', $name );
$return .= <<<IPSCONTENT

						<div class='ipsColumns__secondary i-basis_400 i-padding_3 cOnboard__column cOnboard__column--info'>
							<div class="ipsFluid i-basis_300 i-gap_5">
								<div>
									<h3 class='cOnboard__infoTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_what_are_these', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
									<p>
IPSCONTENT;

$val = "onboard_msg_$key"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
								</div>
								<div>
									<h3 class='cOnboard__infoTitle'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_where_are_these', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
									<p>
IPSCONTENT;

$val = "onboard_location_$key"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
								</div>
							</div>
						</div>

						<div class='ipsColumns__primary cOnboard__column cOnboard__column--form i-padding-block_2'>
							<ul class='ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $form->class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--onboard'>
								
IPSCONTENT;

foreach ( $collection as $input ):
$return .= <<<IPSCONTENT

									
IPSCONTENT;

if ( \is_string( $input ) ):
$return .= <<<IPSCONTENT

										{$input}
									
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

										{$input->rowHtml( $form )}
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

							</ul>
						</div>
					</div>

					<div class='ipsSubmitRow' data-role="sectionButtons">
						<button class='ipsButton ipsButton--inherit' data-action="nextStep" type='button'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'next', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</button>
					</div>
				</div>
				
IPSCONTENT;

$count++;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT


		<div class="ipsBox i-padding_3 i-text-align_center">
			
IPSCONTENT;

$return .= implode( '', $actionButtons);
$return .= <<<IPSCONTENT

		</div>
	</form>
</div>
IPSCONTENT;

		return $return;
}

	function onboardWelcome(  ) {
		$return = '';
		$return .= <<<IPSCONTENT


<div class='ipsBox cOnboardBox cOnboardBox--welcome'>
	<h1 class='ipsTitle ipsTitle--h3'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_pagetitle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h1>
	<p class='cOnboardBox__text i-margin-bottom_4'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_subtitle', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>

	<div class="ipsColumns i-gap_5 i-flex-wrap_wrap-reverse">
		<div class="ipsColumns__primary">
			<div class='cOnboardBox__text ipsRichText'>
				
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_welcome_message', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</div>
		</div>
		<div class='ipsColumns__secondary i-basis_250 i-text-align_center cOnboardBox__image'>
			<img src='
IPSCONTENT;

$return .= \IPS\Theme::i()->resource( "welcome.svg", "core", 'admin', false );
$return .= <<<IPSCONTENT
' alt='' loading="lazy">
		</div>
	</div>
	<div class="ipsButtons ipsButtons--start i-margin-top_4">
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=onboard&initial=1", null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--primary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_startquicksetup', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
		<a href='
IPSCONTENT;

$return .= htmlspecialchars( \IPS\Http\Url::internal( "app=core&module=overview&controller=onboard&do=dismiss" . "&csrfKey=" . \IPS\Session::i()->csrfKey, null, "", array(), 0 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', TRUE );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--text ipsButton--text_secondary'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onboard_skipquicksetup', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
	</div>
</div>

IPSCONTENT;

		return $return;
}

	function onlineAdmins( $admins ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $admins ) ):
$return .= <<<IPSCONTENT

	<i-data>
		<ul class="ipsData ipsData--table ipsData--online-admins">
		
IPSCONTENT;

foreach ( $admins as $admin  ):
$return .= <<<IPSCONTENT

			<li class='ipsData__item'>
				<div class='ipsData__main'>
					<h4 class='ipsData__title'><a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $admin['user']->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' target='_blank' rel="noopener">
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $admin['user']->name, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a>
IPSCONTENT;

if ( $admin['user'] != \IPS\Member::loggedIn() and $admin['session']['session_location'] != "app=core&module=system&controller=login" ):
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( preg_replace( '/csrfKey=([a-zA-Z0-9]+?)&/', '&', $admin['session']['session_url'] ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-arrow-right'></i></a>
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 </h4>
					<p class='ipsData__meta'>
						
IPSCONTENT;

$val = ( $admin['session']['session_running_time'] instanceof \IPS\DateTime ) ? $admin['session']['session_running_time'] : \IPS\DateTime::ts( $admin['session']['session_running_time'] );$return .= $val->html();
$return .= <<<IPSCONTENT

					</p>
				</div>
			</li>
		
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ul>
	</i-data>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function onlineUsers( $online, $chart, $total ) {
		$return = '';
		$return .= <<<IPSCONTENT

<div class="i-padding_3">
	
IPSCONTENT;

if ( \count( $online ) > 0 ):
$return .= <<<IPSCONTENT

		<div class='i-text-align_center i-color_soft i-font-size_2'>
			
IPSCONTENT;

$sprintf = array($total); $pluralize = array( $total ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'onlineUsersDashboard_title', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf, 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT

		</div>
		<div>{$chart}</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<div class="ipsEmpty">
			<i class='fa-solid fa-user-group'></i>
			<p>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_online_users', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</p>
		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

</div>
IPSCONTENT;

		return $return;
}

	function registrations( $chart ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$chart}
IPSCONTENT;

		return $return;
}

	function widgetWrapper( $blockHtml, $info, $collapsed=array() ) {
		$return = '';
		$return .= <<<IPSCONTENT


<li data-widgetKey='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $info['key'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' data-widgetName='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $info['name'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>
	<div class='ipsBox acpWidget_item' data-ips-template="widgetWrapper">
		<h2 class='ipsBox__header' data-widgetCollapse>
			<span>
				<i class='fa-solid fa-
IPSCONTENT;

if ( \in_array( $info['key'], $collapsed ) ):
$return .= <<<IPSCONTENT
caret-right
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
caret-down
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
'></i> 
IPSCONTENT;

$val = "block_{$info['key']}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

			</span>
			<ul class='acpWidget_tools'>
				<li>
					<a href='#' class='acpWidget_reorder ipsJS_show i-cursor_move' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'reorder_widget', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class="fa-solid fa-arrows-up-down-left-right"></i></a>
				</li>
				<li>
					<a href='#' class='acpWidget_close' data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'close_widget', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-xmark'></i></a>
				</li>
			</ul>
		</h2>
		<div data-role="widgetContent" data-widgetCollapsed='
IPSCONTENT;

if ( \in_array( $info['key'], $collapsed ) ):
$return .= <<<IPSCONTENT
true
IPSCONTENT;

else:
$return .= <<<IPSCONTENT
false
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' data-widgetCollapse-content>
			{$blockHtml}
		</div>
	</div>
</li>
IPSCONTENT;

		return $return;
}}