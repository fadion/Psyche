$(document).ready(function()
{
	var gizmo_toolbar = function()
	{
		var gizmo = $('#gizmo-toolbar');
		var csole = $('#gizmo-console');
		var button = $('#gizmo-toolbar .gizmo-console')
		var width = $(document).width() - 23;
		var csole_width = width - 30;
		var closed_width = 85;

		if (localStorage.getItem('gizmo-status') == 'open')
		{
			gizmo.css('width', width);

			if (localStorage.getItem('gizmo-console') == 'open')
			{
				csole.show();
				button.addClass('gizmo-active');
			}
		}
		else
		{
			gizmo.children().not(':last').hide();
			gizmo.children(':last').html('Open');
			gizmo.css('width', closed_width);
		}

		csole.css('width', csole_width);

		$(window).on('resize', function()
		{
			if (gizmo.children('div:first').is(':visible'))
			{
				gizmo.css('width', $(window).width() - 23);
				csole.css('width', $(window).width() - 53);
			}
		});

		gizmo.find('.gizmo-close').on('click', function()
		{
			var self = $(this);

			if (gizmo.children('div:first').is(':visible'))
			{
				csole.slideUp(200, function()
				{
					gizmo.children().not(self).fadeOut(150);

					gizmo.animate({
						'width': closed_width
					}, 400, function()
					{
						self.html('Open');
					});

					button.removeClass('gizmo-active');
					localStorage.setItem('gizmo-status', 'closed');
					localStorage.setItem('gizmo-console', 'closed');
				});
			}
			else
			{
				gizmo.animate({
					'width': width
				}, 400, function()
				{
					self.html('Close');
					gizmo.children().not(self).fadeIn(100);
					localStorage.setItem('gizmo-status', 'open');
				});
			}
		});

		button.on('click', function()
		{
			var self = $(this);

			if (csole.is(':visible'))
			{
				console_hide();
			}
			else
			{
				console_show();
			}
		});

		$(window).on('keyup', function(e)
		{
			if (e.keyCode == 27)
			{
				console_hide();
			}
		})
		.on('keydown', function(e)
		{
			if (e.keyCode == 192 && (e.ctrlKey || e.metaKey) && e.altKey && gizmo.children('div:first').is(':visible'))
			{
				button.click();
			}
		});

		var console_show = function()
		{
			csole.slideDown(200);
			button.addClass('gizmo-active');
			localStorage.setItem('gizmo-console', 'open');
		};

		var console_hide = function()
		{
			csole.slideUp(200);
			button.removeClass('gizmo-active');
			localStorage.setItem('gizmo-console', 'closed');
		};
	};

	gizmo_toolbar();
});