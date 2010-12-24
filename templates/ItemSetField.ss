<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(sapphire/thirdparty/jquery-ui/jquery-ui-1.8rc3.custom.js) %>
<% require javascript(itemsetfield/javascript/ItemSetField.js) %>
<% require css(itemsetfield/css/jquery.ui.smoothness/ui.all.css) %>
<% require css(itemsetfield/css/ItemSetField.css) %>

<div id="$ID" class="itemsetfield <% if Sortable %>itemsetfield-sortable<% end_if %>" rel="$Link">
	<p class="itemsetfield-heading">$Title</p>

	<ul class="itemsetfield-items">
		<% if ItemForms %>
			<% control ItemForms %>$ForTemplate<% end_control %>
		<% else %>
			<li class="itemsetfield-noitems"><% _t('NOITEMS', 'There are no items selected.') %></li>
		<% end_if %>
	</ul>

	<div class="itemsetfield-actions">
		<% control Actions %>
			<a href="$Link" class="itemsetfield-action $ExtraClass">$Name</a>
		<% end_control %>
	</div>
</div>