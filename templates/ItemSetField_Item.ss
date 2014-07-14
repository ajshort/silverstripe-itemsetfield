<li class="itemsetfield-item $EvenOdd">
	<% control Fields %>$FieldHolder<% end_control %>

	<div class="itemsetfield-item-actions">
		<% control Actions %>
			<a class="itemsetfield-action <% if Confirmed %>ss-itemsetfield-confirmed<% end_if %>" href="$Link">$Name</a>
		<% end_control %>
	</div>

	<% if DefaultAction %>
		<a class="itemsetfield-action <% if Confirmed %>ss-itemsetfield-confirmed<% end_if %>" href="$DefaultAction.Link">$Label</a>
	<% else %>
		<span class="label">$Label</span>
	<% end_if %>
</li>
