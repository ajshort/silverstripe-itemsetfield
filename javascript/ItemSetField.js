;(function($){
/**
 * Performs a request, and either pops up a dialog or reloads the form depending
 * on the result.
 */
var request = function(element, title, params, callback) {
	$.ajax($.extend(params, {
		dataType: 'html',
		success: function(data) {
			var holder = $('<div></div>').html(data);
			var el     = holder.children();

			// If we get an ItemSetField back, then replace it
			if (el.length == 1 && el.is('.itemsetfield')) {
				var field = $('#' + el.attr('id'));

				if (field.data('itemsetfield-dialog')) {
					field.data('itemsetfield-dialog').dialog('close');
				}

				field.replaceWith(el);
			} else {
				var dialog = dialogFor(element, title);
				dialog.empty().append(el).dialog('open');
				dialog.find("form").submit(formHandler);
			}
		},
		error: function() {
			alert(ss.i18n._t('ItemSetField.ERROR', 'Couldn\'t execute action'));
		},
		complete: function() {
			callback();
		}
	}));
}

var dialogFor = function(element, title) {
	// If we're already in a dialog, just return it.
	var existing = element.parents('.itemsetfield-dialog');

	if (existing.length) {
		return existing;
	}

	// Otherwise spawn a dialog off the ItemSetField
	var field  = element.parents('.itemsetfield').eq(0);
	var dialog = field.data('itemsetfield-dialog');

	if (!dialog) {
		dialog = $('<div class="itemsetfield-dialog"></div>')
			.appendTo('body')
			.dialog({
				autoOpen:  false,
				title:     title,
				modal:     true,
				width:     parseInt(field.attr("data-popup-width")),
				height:    parseInt(field.attr("data-popup-height")),
				draggable: true,
				resizable: false
			});
		field.data('itemsetfield-dialog', dialog);
	} else {
		dialog.dialog('option', 'title', title);
	}

	return dialog;
}

/**
 * Handles the submission of a form in an item set field dialog.
 */
var formHandler = function() {
	var form    = $(this);
	var actions = $('input.action', form).eq(0);
	var text    = actions.text();

	actions.val(ss.i18n._t('ItemSetField.LOADING', 'Loading...'));
	actions.attr('disabled', true);

	var params = {
		data: form.serialize(),
		url:  form.attr('action'),
		type: form.attr('method') || 'GET'
	};

	request(form, text, params, function() {
		actions.text(text).removeAttr('disabled');
	});

	return false;
};

$('.itemsetfield-sortable').live('hover', function() {
	$('.itemsetfield-items', this).sortable({
		axis: 'y',
		containment: this,
		opacity: .7
	});
});

$('a.itemsetfield-action').live('click', function() {
	var link = $(this);
	var text = link.text();

	if (link.hasClass('ui-state-disabled')) {
		return false;
	}

	if (link.hasClass("ss-itemsetfield-confirmed")) {
		if (!confirm(ss.i18n._t("ItemSetField.AREYOUSURE", "Are you sure?"))) {
			return false;
		}
	}

	link.text(ss.i18n._t('ItemSetField.LOADING', 'Loading...'));
	link.addClass('ui-state-disabled');

	request(link, text, { url: $(this).attr('href') }, function() {
		link.text(text).removeClass('ui-state-disabled');
	});

	return false;
});

$('.itemsetfield-dialog form').live('reset', function(e) {
	$('input, select', this)
		.not('.action, :hidden')
		.val('')
		.removeAttr('checked').removeAttr('selected');
    e.preventDefault();
});

})(jQuery);