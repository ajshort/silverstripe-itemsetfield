<li class="itemsetfield-item $EvenOdd">
	<% control Fields %>$FieldHolder<% end_control %>

	<div class="itemsetfield-item-actions">
		<% control Actions %>
			<a class="itemsetfield-action" href="$Link">$Name</a>
		<% end_control %>
	</div>

	<% if DefaultAction %>
		<a class="itemsetfield-action" href="$DefaultAction.Link">$Label</a>
	<% else %>
		$Label
	<% end_if %>
</li>
