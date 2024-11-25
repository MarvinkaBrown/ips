;(function($,_,undefined){"use strict";ips.controller.register('core.admin.stats.overview',{dateFilters:{'start':null,'end':null,'range':null},initialize:function(){this.on('submit','[data-role="dateFilter"]',this.updateDateFilter);this.on('change','[data-action="toggleApp"]',this.toggleApp);this.on('change','[name="predate"]',this.submitForm);this.on('click','[data-action="cancelDateRange"]',this.cancelDateRange);this.on('stats.ready',this.blockReady);$(document).on('reloadStatsDateFilters',_.bind(this.resendDateFilters,this));this.setup();},setup:function(){this.url=this.scope.attr('data-url');this.dateFilters.range=this.scope.find('[data-role="dateFilter"]').attr('data-defaultRange');},resendDateFilters:function(e){this.trigger('stats.setDateFilters',{dateFilters:this.dateFilters,url:this.url});},cancelDateRange:function(e){e.preventDefault();this.scope.find('select[name="predate"]').val(this.scope.find('.cStatsFilters').attr('data-defaultRange')).change();},submitForm:function(e){var select=$(e.currentTarget);if(select.val()==='-1'){this.scope.find('.cStatsFilters [data-role="formTitle"], select[name="predate"]').hide();this.scope.find('.cStatsFilters button').show();}else{this.scope.find('.cStatsFilters [data-role="formTitle"], select[name="predate"]').show();this.scope.find('.cStatsFilters button').hide();select.closest('form').submit();}},blockReady:function(e,data){$(e.target).trigger('stats.loadBlock',{dateFilters:this.dateFilters,url:this.url});},toggleApp:function(){var enabledApps=_.map(this.scope.find('[data-action="toggleApp"]:checked'),function(app){return $(app).attr('data-toggledApp')});var disabledApps=_.map(this.scope.find('[data-action="toggleApp"]:not( :checked )'),function(app){return $(app).attr('data-toggledApp');});this.scope.find('.cStatTile[data-app]').each(function(){var tile=$(this);if(tile.hasClass('ipsHide')&&enabledApps.indexOf($(this).attr('data-app'))!==-1){tile.css({transform:'scale(0.7)',opacity:"0"}).removeClass('ipsHide').animate({transform:'scale(1)',opacity:"1"});}else if(enabledApps.indexOf($(this).attr('data-app'))===-1){tile.addClass('ipsHide');}});ips.utils.cookie.set('overviewExcludedApps',JSON.stringify(disabledApps),true);},updateDateFilter:function(e){e.preventDefault();e.stopPropagation();this.dateFilters={'start':null,'end':null,'range':this.scope.find('[name="predate"]').val()};if(this.dateFilters.range=='-1'){this.dateFilters.start=this.scope.find('[name="date[start]"]').val();this.dateFilters.end=this.scope.find('[name="date[end]"]').val();}
this.triggerOn('core.admin.stats.overviewBlock','stats.loadBlock',{dateFilters:this.dateFilters,url:this.url});this.triggerOn('core.admin.stats.nodeFilters','stats.setDateFilters',{dateFilters:this.dateFilters,url:this.url});},});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.stats.nodeFilters',{url:null,dateFilters:{'start':null,'end':null,'range':null},initialize:function(){this.on('submit',this.reloadBlock);$(document).on('stats.setDateFilters',_.bind(this.statsReady,this));$(document).on('click.hovercard',_.bind(function(){this.trigger('reloadStatsDateFilters');},this));this.trigger('reloadStatsDateFilters');},statsReady:function(e,data){this.url=data.url;this.dateFilters=data.dateFilters;},reloadBlock:function(e){var blockKey=$(e.currentTarget).attr('data-block');var subblock=$(e.currentTarget).attr('data-subblock');var blockElement=$('[data-role="statsBlock"][data-block="'+blockKey+'"][data-subblock="'+subblock.replace(/\\/g,'\\\\')+'"]');blockElement.attr('data-nodeFilter',$(e.currentTarget).find('[data-role="nodeValue"]').val());e.preventDefault();e.stopPropagation();this.trigger('stats.nodeFilters',{blockToRefresh:blockKey,subblockToRefresh:subblock,url:this.url,dateFilters:this.dateFilters});$(document).trigger('click');}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.stats.overviewBlock',{initialize:function(){Debug.log('init bloc');this.on('stats.loadBlock',this.loadBlock);$(document).on('stats.nodeFilters',_.bind(this.loadBlock,this));this.setup();},setup:function(){this.refresh=null;this.loaded=false;this.currentCounts=[];this.url=null;this.block=this.scope.attr('data-block');this.subblock=this.scope.attr('data-subblock');this.refreshInterval=this.scope.attr('data-refresh')?parseInt(this.scope.attr('data-refresh')):false;this.trigger('stats.ready');},getCounts:function(elem){return _.map(elem.find('[data-number]'),function(thisElem){return parseInt($(thisElem).attr('data-number'))});},startInterval:function(){if(!this.refreshInterval||!this.url){return;}
clearInterval(this.refresh);this.refresh=setInterval(_.bind(this.fetchUpdate,this),this.refreshInterval*1000);},fetchUpdate:function(){var self=this;ips.getAjax()(this.url,{type:'get'}).done(function(response){var newContent=$("<div>"+response+"</div>");var counts=self.getCounts(newContent);var hasDifference=false;if(counts.length!==self.currentCounts.length){hasDifference=true;}else{for(var i=0;i<counts.length;i++){if(counts[i]!==self.currentCounts[i]){hasDifference=true;break;}}}
if(hasDifference){self.scope.addClass('cStatTile--updated').find('[data-role="statBlockContent"]').html(response);self.currentCounts=counts;setTimeout(function(){self.scope.removeClass('cStatTile--updated');},2200);$(document).trigger('contentChange',[self.scope]);}else{Debug.log("No change in values in "+self.block);}});},loadBlock:function(e,data){if(!_.isUndefined(data.blockToRefresh)&&(data.blockToRefresh!=this.block||data.subblockToRefresh!=this.subblock)){Debug.log("Skipping because "+data.blockToRefresh+" does not match "+this.block+" or "+data.subblockToRefresh+" does not match "+this.subblock);return;}
var self=this;this.url=data.url+'&blockKey='+this.block;clearInterval(this.refresh);if(this.loaded){this.loaded=false;this.scope.removeClass('cStatTile--loaded').find('.cStatTile__body').addClass('ipsLoading').end().find('[data-role="statBlockContent"]').hide().html('');}
if(!_.isUndefined(this.subblock)){this.url=this.url+'&subblock='+this.subblock;}
if(data.dateFilters.range!='-1'&&data.dateFilters.range!='0'){this.url=this.url+'&range='+data.dateFilters.range;}else if(data.dateFilters.range=='-1'&&!_.isNull(data.dateFilters.start)){this.url=this.url+'&start='+data.dateFilters.start+'&end='+data.dateFilters.end;}
if(this.scope.attr('data-nodeFilter')){this.url=this.url+'&nodes='+this.scope.attr('data-nodeFilter');}
ips.getAjax()(this.url,{type:'get'}).done(function(response){self.scope.addClass('cStatTile--loaded').find('.cStatTile__body').removeClass('ipsLoading').end().find('[data-role="statBlockContent"]').hide().html(response);self.scope.find('[data-role="statBlockContent"]').fadeIn('fast');self.loaded=true;self.currentCounts=self.getCounts(self.scope);self.startInterval();$(document).trigger('contentChange',[self.scope]);});}});}(jQuery,_));;
;(function($,_,undefined){"use strict";ips.controller.register('core.admin.stats.filtering',{initialize:function(){this.on('click','[data-role="toggleGroupFilter"]',this.toggleGroupFilter);if($('#elGroupFilter').attr('data-hasGroupFilters')=='true'){$('#elGroupFilter').show();}},toggleGroupFilter:function(e){e.preventDefault();if($('#elGroupFilter').is(':visible')){$('#elGroupFilter').find('input[type="checkbox"]').prop('checked',true);$('#elGroupFilter').slideUp();}
else
{$('#elGroupFilter').slideDown();}}});}(jQuery,_));;