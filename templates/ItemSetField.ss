<% require javascript(sapphire/thirdparty/jquery/jquery.js) %>
<% require javascript(sapphire/thirdparty/jquery-ui/jquery-ui-1.8rc3.custom.js) %>
<% require javascript(itemsetfield/javascript/ItemSetField.js) %>
<% require css(sapphire/thirdparty/jquery-ui-themes/smoothness/jquery-ui-1.8rc3.custom.css) %>
<% require css(itemsetfield/css/ItemSetField.css) %>

<div id="$ID" class="itemsetfield <% if Option(Sortable) %>itemsetfield-sortable<% end_if %>" rel="$Link">
	<p class="itemsetfield-heading">$Title</p>

	<ul class="itemsetfield-items">
		<% if ItemForms %>
			<% control ItemForms %>$ForTemplate<% end_control %>
		<% else %>
			<li class="itemsetfield-noitems"><% _t('NOITEMS', 'There are no items selected.') %></li>
		<% end_if %>
	</ul>

	<% if ItemForms.MoreThanOnePage %>
		<div class="itemsetfield-pagination ui-state-default ui-corner-all">
			<% if ItemForms.NotFirstPage %>
				<a class="itemsetfield-action itemsetfield-pagination-prev" href="$ItemForms.PrevLink">
					&laquo; <% _t('PREVIOUS', 'Previous') %>
				</a>
			<% end_if %>
			<% control ItemForms.PaginationSummary(4) %>
				<% if CurrentBool %>
					<span class="itemsetfield-pagination-current">$PageNum</span>
				<% else %>
					<% if Link %>
						<a class="itemsetfield-action" href="$Link">$PageNum</a>
					<% else %>
						&hellip;
					<% end_if %>
				<% end_if %>
			<% end_control %>
			<% if ItemForms.NotLastPage %>
				<a class="itemsetfield-action itemsetfield-pagination-next" href="$ItemForms.NextLink">
					<% _t('NEXT', 'Next') %> &raquo;
				</a>
			<% end_if %>
		</div>
	<% end_if %>

	<div class="itemsetfield-actions">
		<% control Actions %>
			<a href="$Link" class="itemsetfield-action $ExtraClass">$Name</a>
		<% end_control %>
	</div>
</div>