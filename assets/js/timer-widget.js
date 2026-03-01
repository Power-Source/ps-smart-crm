/**
 * PS Smart CRM - Timer Widget JavaScript
 * 
 * Handles timer functionality, AJAX communication, and UI updates
 * 
 * @version 1.0.0
 */

var wpsCRMTimerWidget = (function($) {
	'use strict';
	
	var widget = {
		activeTimer: null,
		intervalId: null,
		startTime: 0,
		elapsedSeconds: 0,
		isPaused: false,
		
		/**
		 * Initialize Widget
		 */
		init: function() {
			this.checkActiveTimer();
		},
		
		/**
		 * Check for Active Timer on Load
		 */
		checkActiveTimer: function() {
			$.ajax({
				url: wpsCRMTimer.ajax_url,
				type: 'POST',
				data: {
					action: 'wpscrm_get_active_timer',
					nonce: wpsCRMTimer.nonce
				},
				success: function(response) {
					if (response.success && response.data.active) {
						widget.activeTimer = response.data.entry;
						widget.elapsedSeconds = response.data.elapsed_seconds;
						widget.isPaused = (response.data.entry.status === 'paused');
						widget.showActiveTimer();
						
						if (!widget.isPaused) {
							widget.startClock();
						}
					}
				}
			});
		},
		
		/**
		 * Start Timer
		 */
		start: function() {
			var formData = {
				action: 'wpscrm_start_timer',
				nonce: wpsCRMTimer.nonce,
				customer_id: $('#timer-customer').val(),
				project_name: $('#timer-project').val(),
				task_description: $('#timer-task').val(),
				hourly_rate: $('#timer-rate').val(),
				is_billable: $('#timer-billable').is(':checked') ? 1 : 0
			};
			
			$.ajax({
				url: wpsCRMTimer.ajax_url,
				type: 'POST',
				data: formData,
				success: function(response) {
					if (response.success) {
						widget.activeTimer = { id: response.data.entry_id };
						widget.elapsedSeconds = 0;
						widget.isPaused = false;
						widget.showActiveTimer();
						widget.startClock();
						widget.showMessage(response.data.message, 'success');
					} else {
						widget.showMessage(response.data.message, 'error');
					}
				},
				error: function() {
					widget.showMessage('Fehler beim Starten des Timers.', 'error');
				}
			});
		},
		
		/**
		 * Stop Timer
		 */
		stop: function() {
			if (!widget.activeTimer) {
				return;
			}
			
			if (!confirm('Timer beenden und Zeit speichern?')) {
				return;
			}
			
			$.ajax({
				url: wpsCRMTimer.ajax_url,
				type: 'POST',
				data: {
					action: 'wpscrm_stop_timer',
					nonce: wpsCRMTimer.nonce,
					entry_id: widget.activeTimer.id
				},
				success: function(response) {
					if (response.success) {
						widget.stopClock();
						widget.resetTimer();
						widget.showMessage(response.data.message, 'success');
						
						// Reload page after 1.5 seconds to show updated history
						setTimeout(function() {
							location.reload();
						}, 1500);
					} else {
						widget.showMessage(response.data.message, 'error');
					}
				},
				error: function() {
					widget.showMessage('Fehler beim Stoppen des Timers.', 'error');
				}
			});
		},
		
		/**
		 * Pause/Resume Timer
		 */
		pause: function() {
			if (!widget.activeTimer) {
				return;
			}
			
			$.ajax({
				url: wpsCRMTimer.ajax_url,
				type: 'POST',
				data: {
					action: 'wpscrm_pause_timer',
					nonce: wpsCRMTimer.nonce,
					entry_id: widget.activeTimer.id
				},
				success: function(response) {
					if (response.success) {
						widget.isPaused = (response.data.status === 'paused');
						
						if (widget.isPaused) {
							widget.stopClock();
							$('#timer-status').removeClass('running').addClass('paused').text('Pausiert');
							$('#btn-pause').html('▶ Fortsetzen');
						} else {
							widget.startClock();
							$('#timer-status').removeClass('paused').addClass('running').text('Läuft');
							$('#btn-pause').html('⏸ Pause');
						}
						
						widget.showMessage(response.data.message, 'success');
					} else {
						widget.showMessage(response.data.message, 'error');
					}
				},
				error: function() {
					widget.showMessage('Fehler beim Pausieren des Timers.', 'error');
				}
			});
		},
		
		/**
		 * Start Clock Display
		 */
		startClock: function() {
			if (widget.intervalId) {
				clearInterval(widget.intervalId);
			}
			
			widget.intervalId = setInterval(function() {
				widget.elapsedSeconds++;
				widget.updateClockDisplay();
			}, 1000);
		},
		
		/**
		 * Stop Clock Display
		 */
		stopClock: function() {
			if (widget.intervalId) {
				clearInterval(widget.intervalId);
				widget.intervalId = null;
			}
		},
		
		/**
		 * Update Clock Display
		 */
		updateClockDisplay: function() {
			var hours = Math.floor(widget.elapsedSeconds / 3600);
			var minutes = Math.floor((widget.elapsedSeconds % 3600) / 60);
			var seconds = widget.elapsedSeconds % 60;
			
			var display = 
				String(hours).padStart(2, '0') + ':' +
				String(minutes).padStart(2, '0') + ':' +
				String(seconds).padStart(2, '0');
			
			$('#timer-clock').text(display);
		},
		
		/**
		 * Show Active Timer UI
		 */
		showActiveTimer: function() {
			$('#timer-form').hide();
			$('#timer-info').show();
			$('#btn-start').hide();
			$('#btn-pause').show();
			$('#btn-stop').show();
			
			var statusClass = widget.isPaused ? 'paused' : 'running';
			var statusText = widget.isPaused ? 'Pausiert' : 'Läuft';
			
			$('#timer-status')
				.removeClass('stopped running paused')
				.addClass(statusClass)
				.text(statusText);
			
			if (widget.activeTimer.project_name) {
				$('#info-project').text(widget.activeTimer.project_name);
			}
			if (widget.activeTimer.task_description) {
				$('#info-task').text(widget.activeTimer.task_description);
			}
			
			widget.updateClockDisplay();
		},
		
		/**
		 * Reset Timer UI
		 */
		resetTimer: function() {
			widget.activeTimer = null;
			widget.elapsedSeconds = 0;
			widget.isPaused = false;
			
			$('#timer-form').show();
			$('#timer-info').hide();
			$('#btn-start').show();
			$('#btn-pause').hide();
			$('#btn-stop').hide();
			
			$('#timer-status')
				.removeClass('running paused')
				.addClass('stopped')
				.text('Bereit');
			
			$('#timer-clock').text('00:00:00');
			
			// Reset form
			$('#timer-customer').val('');
			$('#timer-project').val('');
			$('#timer-task').val('');
			$('#timer-rate').val('0');
			$('#timer-billable').prop('checked', true);
		},
		
		/**
		 * Show Message
		 */
		showMessage: function(message, type) {
			// Simple alert for now
			// TODO: Implement better notification system
			if (type === 'error') {
				alert('❌ ' + message);
			} else {
				// Success messages können subtiler sein
				console.log('✅ ' + message);
			}
		}
	};
	
	return widget;
	
})(jQuery);
