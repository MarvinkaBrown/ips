ips.templates.set('vse.classes.title'," <li class='ipsToolbox_sectionTitle' data-role='{{role}}'>{{title}}</li>");ips.templates.set('vse.classes.item'," <li data-styleID='{{styleid}}' data-themeKey='{{themekey}}'>	{{#swatch.back}}		<input class='vseClass_swatch vseClass_swatch--back' value='{{swatch.back.color}}' data-key='{{swatch.back.key}}'>	{{/swatch.back}}	{{^swatch.back}}	 	<span class='vseClass_swatch vseClass_swatch--back vseClass_swatch--noStyle'>&times;</span>	{{/swatch.back}}	{{#swatch.fore}}		<input class='vseClass_swatch vseClass_swatch--fore' value='{{swatch.fore.color}}' data-key='{{swatch.fore.key}}'>	{{/swatch.fore}}	{{^swatch.fore}}	 	<span class='vseClass_swatch vseClass_swatch--fore vseClass_swatch--noStyle'>&times;</span>	{{/swatch.fore}}	{{title}}</li>");ips.templates.set('vse.panels.header'," 	<h2 class='ipsTitle ipsTitle--h3'>{{title}}</h2>	{{#desc}}		<p class='i-color_soft i-font-size_-2'>			{{desc}}		</p>	{{/desc}}	<br>");ips.templates.set('vse.panels.wrapper'," 	<div class='vseStyleSection' data-role='{{type}}Panel'>		{{{content}}}	</div>");ips.templates.set('vse.panels.background'," 	<h3>{{#lang}}vseBackground{{/lang}}</h3>	<div data-role='backgroundControls' class='ipsSpanGrid'>		<div class='ipsGrid_span3'>			<div data-role='backgroundPreview' class='vseBackground_preview'>&nbsp;</div>		</div>		<div class='ipsGrid_span9'>			<input type='text' class='ipsInput--wide color vseBackground_color' data-role='backgroundColor' value='{{backgroundColor}}'>			<br>			<div class='ipsSpanGrid'>				<!--<div class='ipsGrid_span6'>					<button data-ipsTooltip title='{{#lang}}vseBackground_image{{/lang}}' class='ipsButton ipsButton--primary ipsButton--small ipsButton--wide i-text-align_center i-font-size_2'><i class='fa-regular fa-image'></i></button>				</div>-->				<div class='ipsGrid_span6'>					<button data-ipsTooltip title='{{#lang}}vseBackground_gradient{{/lang}}' data-action='launchGradientEditor' class='ipsButton ipsButton--primary ipsButton--small ipsButton--wide i-text-align_center i-font-size_2'><i class='fa-solid fa-barcode'></i></button>				</div>			</div>		</div>	</div>");ips.templates.set('vse.panels.font'," 	<h3>{{#lang}}vseFont_color{{/lang}}</h3>	<input type='text' class='ipsInput--wide color' data-role='fontColor' value='{{fontColor}}'>");ips.templates.set('vse.gradient.editor'," 	<div data-role='gradientPreview' class='vseBackground_gradient'></div>	<div class='ipsSpanGrid'>		<button data-action='gradientAngle' data-angle='90' class='ipsButton ipsButton--primary ipsButton--small ipsGrid_span3'>				<i class='fa-solid fa-arrow-down'></i>		</button>		<button data-action='gradientAngle' data-angle='0' class='ipsButton ipsButton--primary ipsButton--small ipsGrid_span3'>			<i class='fa-solid fa-arrow-left'></i>		</button>		<button data-action='gradientAngle' data-angle='45' class='ipsButton ipsButton--primary ipsButton--small ipsGrid_span3'>			<i class='fa-solid fa-arrow-up'></i>		</button>		<button data-action='gradientAngle' data-angle='120' class='ipsButton ipsButton--primary ipsButton--small ipsGrid_span3'>			<i class='fa-solid fa-arrow-right'></i>		</button>	</div>	<hr class='ipsHr'>	<ul data-role='gradientStops'>		<li class='ipsSpanGrid'>			<p class='ipsGrid_span1'>&nbsp;</p>			<p class='i-color_soft i-font-size_-2 ipsGrid_span5'>{{#lang}}vseGradient_color{{/lang}}</p>			<p class='i-color_soft i-font-size_-2 ipsGrid_span6'>{{#lang}}vseGradient_position{{/lang}}</p>		</li>		<li class='ipsSpanGrid'>			<p class='ipsGrid_span1'>&nbsp;</p>			<p class='ipsGrid_span11'><a href='#' data-action='gradientAddStop'>{{#lang}}vseAddStop{{/lang}}</a></p>		</li>	</ul>	<hr class='ipsHr'>	<div class='ipsSpanGrid'>		{{{buttons}}}	</div>");ips.templates.set('vse.gradient.twoButtons',"	<button data-action='saveGradient' class='ipsGrid_span8 ipsButton ipsButton--secondary ipsButton--small ipsButton--wide'>{{#lang}}vseGradient_save{{/lang}}</button>	<button data-action='cancelGradient' class='ipsGrid_span4 ipsButton ipsButton--secondary ipsButton--small ipsButton--wide'>{{#lang}}vseCancel{{/lang}}</button>");ips.templates.set('vse.gradient.threeButtons',"	<button data-action='saveGradient' class='ipsGrid_span4 ipsButton ipsButton--secondary ipsButton--small ipsButton--wide'>{{#lang}}vseSave{{/lang}}</button>	<button data-action='cancelGradient' class='ipsGrid_span4 ipsButton ipsButton--secondary ipsButton--small ipsButton--wide'>{{#lang}}vseCancel{{/lang}}</button>	<button data-action='removeGradient' class='ipsGrid_span4 ipsButton ipsButton--primary ipsButton--small ipsButton--wide'>{{#lang}}vseDelete{{/lang}}</button>");ips.templates.set('vse.gradient.stop'," 	<li class='ipsSpanGrid'>		<span class='ipsGrid_span1 i-color_soft i-text-align_center'><i class='fa-solid fa-bars'></i></span>		<input type='text' class='ipsGrid_span5' value='{{color}}' maxlength='6' pattern='^([0-9a-zA-Z]{6})$'>		<input type='range' class='ipsGrid_span5' min='0' max='100' value='{{location}}'>		<p class='i-text-align_center ipsGrid_span1'><a href='#' data-action='gradientRemoveStop'><i class='fa-solid fa-xmark'></i></a></p>	</li>");ips.templates.set('vse.colorizer.panel'," 	<p class='i-color_soft i-padding_3'>		{{#lang}}vseColorizer_desc{{/lang}}	</p>	<div class='i-padding_3'>		<div class='ipsSpanGrid'>			<div class='ipsGrid_span5 i-text-align_center'>				<input type='text' class='vseColorizer_swatch color' data-role='primaryColor' value='{{primaryColor}}'>				<span class='i-color_soft'>{{#lang}}vseColorizer_primary{{/lang}}</span>			</div>			<div class='ipsGrid_span2'></div>			<div class='ipsGrid_span5 i-text-align_center'>				<input type='text' class='vseColorizer_swatch color' data-role='secondaryColor' value='{{secondaryColor}}'>				<span class='i-color_soft'>{{#lang}}vseColorizer_secondary{{/lang}}</span>			</div>		</div>		<br>		<div class='ipsGrid_span4 i-text-align_center'>			<input type='text' class='vseColorizer_swatch color' data-role='textColor' value='{{textColor}}'>			<span class='i-color_soft'>{{#lang}}vseColorizer_text{{/lang}}</span>		</div>		<br><br>		<button class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide' data-action='invertColors'>{{#lang}}vseColorizer_invert{{/lang}}</button>		<br>		<button class='ipsButton ipsButton--soft ipsButton--small ipsButton--wide' data-action='revertColorizer' disabled>{{#lang}}vseColorizer_revert{{/lang}}</button>	</div>");;