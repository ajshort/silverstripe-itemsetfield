<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(sapphire/thirdparty/jquery-livequery/jquery.livequery.js) %>
<% require javascript(sapphire/thirdparty/jquery-ui/jquery-ui-1.8rc3.custom.js) %>
<% require css(itemsetfield/css/jquery.ui.smoothness/ui.all.css) %>
<% require css(itemsetfield/css/itemsetfield.css) %>
<% require javascript(itemsetfield/javascript/ItemSetField.js) %>

<div id="$ID" class='item-set-field <% if Sortable %>sortable<% end_if %>' rel='$Link'>
	<p class="heading">$Title</p>
	<ul>
		<% if ItemForms %>
			<% control ItemForms %>$ForTemplate<% end_control %>
		<% else %>
			<li class="no-items-text"><% _t('NOITEMS', 'There are no items selected.') %></li>
		<% end_if %>
	</ul>

	<div class='item-set-field-actions'>	
		<% control Actions %>
			<input class='item-set-field-action $ExtraClass' type='button' value='$Name' rel='$Link'/>
		<% end_control %>
	</div>
</div>