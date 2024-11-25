<?php
namespace IPS\Theme;
class class_forums_front_widgets extends \IPS\Theme\Template
{	function forumStatistics( $stats, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

<h3 class='ipsWidget__header'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'block_forumStatistics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</h3>
<div class="ipsWidget__content">
	<ul class='ipsList ipsList--stats ipsList--stacked ipsList--border ipsList--fill'>
		<li>
			<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_topics', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $stats['total_topics'] );
$return .= <<<IPSCONTENT
</span>
		</li>
		<li>
			<strong class='ipsList__label'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'total_posts', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</strong>
			<span class='ipsList__value'>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $stats['total_posts'] );
$return .= <<<IPSCONTENT
</span>
		</li>
	</ul>
</div>
IPSCONTENT;

		return $return;
}

	function poll( $topic, $poll, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT

{$poll}

IPSCONTENT;

		return $return;
}

	function pollFormWidget( $url, $id, $action, $elements, $hiddenValues, $actionButtons, $uploadField, $class='', $attributes=array(), $sidebar=NULL, $form=NULL ) {
		$return = '';
		$return .= <<<IPSCONTENT

<form accept-charset='utf-8' class="ipsForm 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $class, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 ipsForm--poll-widget" action="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString( 'do', 'widgetPoll' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" method="post" 
IPSCONTENT;

if ( $uploadField ):
$return .= <<<IPSCONTENT
enctype="multipart/form-data"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
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
 data-ipsForm>
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

		<ol class='ipsPollList ipsPollList--questions'>
			
IPSCONTENT;

$i = 0;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

foreach ( $collection as $idx => $input ):
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$i++;
$return .= <<<IPSCONTENT

				<li class='ipsFieldRow ipsFieldRow--noLabel'>
					<h4 class='i-font-weight_600'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
. 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $input->label, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h4>
					
IPSCONTENT;

if ( !$input->options['multiple'] ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->radio( $input->name, $input->value, $input->required, $input->options['options'], $input->options['disabled'], '', $input->options['disabled'] );
$return .= <<<IPSCONTENT

					
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "forms", "core", 'global' )->checkboxset( $input->name, $input->value, $input->required, $input->options['options'], FALSE, NULL, $input->options['disabled'], $input->options['toggles'], NULL, NULL, 'all', array(), TRUE, isset( $input->options['descriptions'] ) ? $input->options['descriptions'] : NULL, FALSE, FALSE, $input->options['condense'] );
$return .= <<<IPSCONTENT

                    
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $input->error ):
$return .= <<<IPSCONTENT

						<div class="ipsFieldRow__warning">
IPSCONTENT;

$val = "{$input->error}"; $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</div>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

		</ol>
	
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

	<div class='ipsSubmitRow'>
		<ul class="ipsButtons ipsButtons--fill">
			
IPSCONTENT;

foreach ( $actionButtons as $button ):
$return .= <<<IPSCONTENT

				<li>{$button}</li>
			
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			<li>
				<a class='ipsButton ipsButton--inherit' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_results_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('do', 'widgetPoll')->setQueryString( array( '_poll' => 'results', 'nullVote' => 1 ) )->csrf(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( !\IPS\Settings::i()->allow_result_view ):
$return .= <<<IPSCONTENT
data-viewResults-confirm="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'warn_allow_result_view', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-action='viewResults'>
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_results', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</a>
			</li>
			<li>
				<a class='ipsButton ipsButton--inherit' title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_results_title', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
					
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_view_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

				</a>
			</li>
		</ul>
	</div>
</form>
IPSCONTENT;

		return $return;
}

	function pollWidget( $poll, $url, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->fetchPoll ) ):
$return .= <<<IPSCONTENT

<section data-controller='core.front.core.poll'>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	
IPSCONTENT;

if ( $poll->canVote() and \IPS\Widget\Request::i()->_poll != 'results' and ( !$poll->getVote() or \IPS\Widget\Request::i()->_poll == 'form') and $pollForm = $poll->buildForm() ):
$return .= <<<IPSCONTENT

		<h3 class='ipsWidget__header'>
			<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->poll_question, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			<span class='ipsWidget__header-secondary i-color_soft' data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $poll->votes ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_num_votes', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'><i class='fa-regular fa-check-square'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->votes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		</h3>
		<div class='ipsWidget__content' data-role='pollContents'>
			{$pollForm->customTemplate( array( \IPS\Theme::i()->getTemplate( 'widgets', 'forums', 'front' ), 'pollFormWidget' ), $url )}
		</div>
	
IPSCONTENT;

elseif ( ( $poll->canViewResults() and !$poll->canVote() ) or $poll->getVote() or ( \IPS\Widget\Request::i()->_poll == 'results' and \IPS\Settings::i()->allow_result_view ) ):
$return .= <<<IPSCONTENT

		<h3 class='ipsWidget__header'>
			<span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->poll_question, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
			<span class='ipsWidget__header-secondary i-color_soft' data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $poll->votes ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_num_votes', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'><i class='fa-regular fa-check-square'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->votes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		</h3>
		<div class='ipsWidget__content' data-role='pollContents'>
			<i-data>
				<ol class='ipsPollList ipsData'>
					
IPSCONTENT;

$i = 0;
$return .= <<<IPSCONTENT

					
IPSCONTENT;

foreach ( $poll->choices as $questionId => $question ):
$return .= <<<IPSCONTENT

						
IPSCONTENT;

$i++;
$return .= <<<IPSCONTENT

						<li class='ipsData__item'>
							<div>
								<h3 class='ipsTitle ipsTitle--h6'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $i, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
. 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $question['question'], ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
								<ul class='ipsPollList_choices i-grid i-gap_2 i-margin-top_3'>
									
IPSCONTENT;

foreach ( $question['choice'] as $k => $choice ):
$return .= <<<IPSCONTENT

										<li>
											<div class='i-font-weight_500 i-margin-bottom_1'>{$choice}</div>
											<progress class='ipsProgress' max="100" value='
IPSCONTENT;

if ( array_sum( $question['votes'] ) > 0  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \intval( ( $question['votes'][ $k ] / array_sum( $question['votes'] ) ) * 100 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
' title='
IPSCONTENT;

if ( array_sum( $question['votes'] ) > 0  ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$return .= \IPS\Theme\Template::htmlspecialchars( \intval( ( $question['votes'][ $k ] / array_sum( $question['votes'] ) ) * 100 ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT
0
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
%' data-ipstooltip></progress>
										</li>
									
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

								</ul>
							</div>
						</li>
					
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				</ol>
			</i-data>
			<div class='ipsSubmitRow'>
				<ul class='ipsButtons ipsButtons--fill'>
					
IPSCONTENT;

if ( $poll->canVote() ):
$return .= <<<IPSCONTENT

						<li>
							<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url->setQueryString('do', 'widgetPoll')->setQueryString( '_poll', 'form' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_vote_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
' class='ipsButton ipsButton--inherit' data-action='viewResults'><i class='fa-solid fa-caret-left'></i><span>
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'show_vote_options', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</span></a>
						</li>
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					<li>
						<a class='ipsButton ipsButton--inherit' href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_view_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
</a>
					</li>
				</ul>
				
IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT

					<div class='i-margin-top_2 i-text-align_center'>
						
IPSCONTENT;

$sprintf = array(\IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ), \IPS\Http\Url::internal( 'app=core&module=system&controller=register', 'front', 'register' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_guest', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

					</div>
				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
		</div>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<h3 class='ipsWidget__header'>
			<span>
				
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->poll_question, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

			</span>
			<span class='ipsWidget__header-secondary' data-ipsTooltip title='
IPSCONTENT;

$pluralize = array( $poll->votes ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_num_votes', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
'><i class='fa-regular fa-check-square'></i> 
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $poll->votes, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span>
		</h3>
		<div class='ipsWidget__padding ipsWidget__content' data-role='pollContents'>
			
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'no_permission_poll', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

if ( !\IPS\Member::loggedIn()->member_id ):
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$sprintf = array(\IPS\Http\Url::internal( 'app=core&module=system&controller=login', 'front', 'login' ), \IPS\Http\Url::internal( 'app=core&module=system&controller=register', 'front', 'register' )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'poll_guest', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !isset( \IPS\Widget\Request::i()->fetchPoll ) ):
$return .= <<<IPSCONTENT

</section>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function postFeed( $comments, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $comments )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-topic-feed_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content'>
		<i-data>
			<ul class='ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--widget-forums-postFeed' 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $comments as $comment ):
$return .= <<<IPSCONTENT

					<li class='ipsData__item'>
						<a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
						
IPSCONTENT;

if ( in_array($layout, array("wallpaper", "featured", "grid")) ):
$return .= <<<IPSCONTENT

							<figure class='ipsData__image'>
								
IPSCONTENT;

if ( $image = $comment->item()->primaryImage() ):
$return .= <<<IPSCONTENT

									<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
								
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

									<i></i>
								
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

							</figure>
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							<figure class='ipsData__icon'>
								
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $comment->author(), 'fluid' );
$return .= <<<IPSCONTENT

							</figure>
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						<div class="ipsData__content">
							<div class='ipsData__main'>
								<div class='ipsData__title'>
									<h4><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title='
IPSCONTENT;

$sprintf = array($comment->item()->title); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'view_this_topic', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'sprintf' => $sprintf ) );
$return .= <<<IPSCONTENT
'>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</a></h4>
									
IPSCONTENT;

if ( $comment->item()->isSolved() ):
$return .= <<<IPSCONTENT

										<div class='ipsBadges'>
											<span class="ipsBadge ipsBadge--icon ipsBadge--positive" data-ipsTooltip title='
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'this_is_solved', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
'><i class='fa-solid fa-check'></i></span>
										</div>
									
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

								</div>
								<div class='ipsData__desc ipsRichText' data-controller='core.front.core.lightboxedImages'>{$comment->truncated()}</div>
								<p class='ipsData__meta'>
IPSCONTENT;

$htmlsprintf = array($comment->author()->link( NULL, NULL, $comment->isAnonymous() )); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_itemprop', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT
 <a href='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $comment->item()->url()->setQueryString( array( 'do' => 'findComment', 'comment' => $comment->pid ) ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
'>{$comment->dateLine()}</a></p>
							</div>
						</div>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT


IPSCONTENT;

		return $return;
}

	function topicFeed( $topics, $title, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

if ( !empty( $topics )  ):
$return .= <<<IPSCONTENT

	<header class='ipsWidget__header'>
		<h3>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $title, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</h3>
		
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$carouselID = 'widget-topic-feed_' . mt_rand();
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->carouselNavigation( $carouselID );
$return .= <<<IPSCONTENT

		
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	</header>
	<div class='ipsWidget__content'>
		<i-data>
			<ul class="ipsData ipsData--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $layout, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
ipsData--carousel
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 ipsData--topic-feed-widget" 
IPSCONTENT;

if ( $isCarousel ):
$return .= <<<IPSCONTENT
id='
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $carouselID, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
' tabindex="0"
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
				
IPSCONTENT;

foreach ( $topics as $topic ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "widgets", "forums", 'front' )->topicFeedRow( $topic, $layout );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			</ul>
		</i-data>
	</div>

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}

	function topicFeedRow( $topic, $layout='table', $isCarousel=false ) {
		$return = '';
		$return .= <<<IPSCONTENT


IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "row:before", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<li data-ips-hook="row" class="ipsData__item 
IPSCONTENT;

if ( method_exists( $topic, 'tableClass' ) && $topic->tableClass() ):
$return .= <<<IPSCONTENT
ipsData__item--
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->tableClass(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 
IPSCONTENT;

if ( $topic->hidden() ):
$return .= <<<IPSCONTENT
ipsModerated
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "row:inside-start", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

	<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" class="ipsLinkPanel" aria-hidden="true" tabindex="-1"><span>
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
</span></a>
	
IPSCONTENT;

if ( in_array($layout, array("wallpaper", "featured", "grid")) ):
$return .= <<<IPSCONTENT

		<figure class="ipsData__image">
			
IPSCONTENT;

if ( $image = $topic->primaryImage() ):
$return .= <<<IPSCONTENT

				<img src="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $image->url, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" alt="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->mapped( 'title' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" loading="lazy">
			
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

				<i></i>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</figure>
	
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

		<figure class="ipsData__icon">
			
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $topic->author(), 'fluid' );
$return .= <<<IPSCONTENT

		</figure>
	
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

	<div class="ipsData__content">
		<div class="ipsData__main">
			
IPSCONTENT;

if ( \IPS\Widget\Request::i()->controller != 'forums' ):
$return .= <<<IPSCONTENT

				<div class="ipsData__category"><a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->container()->url(), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
">{$topic->container()->_formattedTitle}</a></div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			<div class="ipsData__title">
				
IPSCONTENT;

if ( $topic->prefix() ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->prefix( $topic->prefix( TRUE ), $topic->prefix() );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "title:before", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<h4 data-ips-hook="title">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "title:inside-start", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

					<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( "getPrefComment" ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $topic->tableHoverUrl AND $topic->canView() ):
$return .= <<<IPSCONTENT
data-ipshover data-ipshover-target="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url()->setQueryString('preview', 1), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" data-ipshover-timeout="1.5" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->mapped('title'), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT

					</a>
				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "title:inside-end", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</h4>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "title:after", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "badges:before", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<div data-ips-hook="badges" class="ipsBadges">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "badges:inside-start", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				    
IPSCONTENT;

foreach ( $topic->badges() as $badge ):
$return .= <<<IPSCONTENT
{$badge}
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "badges:inside-end", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "badges:after", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( \in_array( $layout, array("list")) ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

if ( $topic->commentPageCount() > 1 ):
$return .= <<<IPSCONTENT

						{$topic->commentPagination( array(), 'miniPagination' )}
					
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

			</div>
			<div class="ipsData__meta">
				
IPSCONTENT;

$htmlsprintf = array($topic->author()->link( NULL, NULL, $topic->isAnonymous() ), \IPS\DateTime::ts( $topic->mapped('date') )->html(FALSE)); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'byline_name_date', ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'htmlsprintf' => $htmlsprintf ) );
$return .= <<<IPSCONTENT

			</div>
			
IPSCONTENT;

if ( $layout === "featured" ):
$return .= <<<IPSCONTENT

				<div class="ipsData__desc">{$topic->truncated(TRUE)}</div>
			
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

		</div>
		<div class="ipsData__extra">
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "stats:before", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<ul data-ips-hook="stats" class="ipsData__stats">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "stats:inside-start", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

foreach ( $topic->stats(FALSE) as $k => $v ):
$return .= <<<IPSCONTENT

					<li 
IPSCONTENT;

if ( \in_array( $k, $topic->hotStats ) ):
$return .= <<<IPSCONTENT
class="ipsData__stats-hot" data-text="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'hot_item_desc', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
 data-stattype="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $k, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

if ( $v === 0 ):
$return .= <<<IPSCONTENT
data-v="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $v, ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" 
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT
>
						<span class="ipsData__stats-icon" data-stat-value="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
" aria-hidden="true" data-ipstooltip title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumberShort( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize, 'format' => 'short' ) );
$return .= <<<IPSCONTENT
"></span>
						<span class="ipsData__stats-label">
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->formatNumber( $v );
$return .= <<<IPSCONTENT
 
IPSCONTENT;

$val = "{$k}"; $pluralize = array( $v ); $return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( $val, ENT_DISALLOWED, 'UTF-8', FALSE ), FALSE, array( 'pluralize' => $pluralize ) );
$return .= <<<IPSCONTENT
</span>
					</li>
				
IPSCONTENT;

endforeach;
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "stats:inside-end", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</ul>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "stats:after", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestAuthorPhoto:before", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<div class="ipsData__last ipsData__last--author" data-ips-hook="latestAuthorPhoto">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestAuthorPhoto:inside-start", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $topic->author(), 'fluid' );
$return .= <<<IPSCONTENT

				<div class="ipsData__last-text">
					<div class="ipsData__last-primary">
						{$topic->author()->link( NULL, NULL, $topic->isAnonymous() )}
					</div>
					<div class="ipsData__last-secondary">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

$val = ( $topic->mapped('date') instanceof \IPS\DateTime ) ? $topic->mapped('date') : \IPS\DateTime::ts( $topic->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

						</a>
					</div>
				</div>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestAuthorPhoto:inside-end", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestAuthorPhoto:after", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestUserPhoto:before", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
<div class="ipsData__last" data-ips-hook="latestUserPhoto">
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestUserPhoto:inside-start", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

if ( $topic->mapped('num_comments') ):
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $topic->lastCommenter(), 'fluid' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

					
IPSCONTENT;

$return .= \IPS\Theme::i()->getTemplate( "global", "core" )->userPhoto( $topic->author(), 'fluid' );
$return .= <<<IPSCONTENT

				
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

				<div class="ipsData__last-text">
					<div class="ipsData__last-primary">
						
IPSCONTENT;

if ( $topic->mapped('num_comments') ):
$return .= <<<IPSCONTENT

							{$topic->lastCommenter()->link()}
						
IPSCONTENT;

else:
$return .= <<<IPSCONTENT

							{$topic->author()->link( NULL, NULL, $topic->isAnonymous() )}
						
IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

					</div>
					<div class="ipsData__last-secondary">
						<a href="
IPSCONTENT;
$return .= \IPS\Theme\Template::htmlspecialchars( $topic->url( 'getLastComment' ), ENT_QUOTES | ENT_DISALLOWED, 'UTF-8', FALSE );
$return .= <<<IPSCONTENT
" title="
IPSCONTENT;

$return .= \IPS\Member::loggedIn()->language()->addToStack( htmlspecialchars( 'get_last_post', ENT_DISALLOWED, 'UTF-8', FALSE ), TRUE, array(  ) );
$return .= <<<IPSCONTENT
">
							
IPSCONTENT;

if ( $topic->mapped('last_comment') ):
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $topic->mapped('last_comment') instanceof \IPS\DateTime ) ? $topic->mapped('last_comment') : \IPS\DateTime::ts( $topic->mapped('last_comment') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

else:
$return .= <<<IPSCONTENT

IPSCONTENT;

$val = ( $topic->mapped('date') instanceof \IPS\DateTime ) ? $topic->mapped('date') : \IPS\DateTime::ts( $topic->mapped('date') );$return .= $val->html();
$return .= <<<IPSCONTENT

IPSCONTENT;

endif;
$return .= <<<IPSCONTENT

						</a>
					</div>
				</div>
			
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestUserPhoto:inside-end", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</div>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "latestUserPhoto:after", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

		</div>
	</div>

IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "row:inside-end", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT
</li>
IPSCONTENT;

$return .= \IPS\Theme\CustomTemplate::getCustomTemplatesForHookPoint( "forums/front/widgets/topicFeedRow", "row:after", [ $topic,$layout,$isCarousel ] );
$return .= <<<IPSCONTENT

IPSCONTENT;

		return $return;
}}