/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Install/CreateAdmin
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Install/PasswordStrength',
  'TYPO3/CMS/Backend/Notification'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, PasswordStrength, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorCreateForm: '#t3js-createAdmin-form',
    selectorCreateToken: '#t3js-createAdmin-token',
    selectorCreateTrigger: '.t3js-createAdmin-create',
    selectorOutputContainer: '.t3js-createAdmin-output',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      self.getData();

      currentModal.on('click', this.selectorCreateTrigger, function(e) {
        e.preventDefault();
        self.create();
      });

      currentModal.on('click', '.t3-install-form-password-strength', function(e) {
        PasswordStrength.initialize('.t3-install-form-password-strength');
      });
    },

    getData: function() {
      var self = this;
      var modalContent = this.currentModal.find(self.selectorModalBody);
      $.ajax({
        url: Router.getUrl('createAdminGetData'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            modalContent.empty().append(data.html);
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    create: function() {
      var self = this;
      var executeToken = self.currentModal.find(this.selectorCreateToken).text();
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'action': 'createAdmin',
            'token': executeToken,
            'userName': self.currentModal.find('.t3js-createAdmin-user').val(),
            'userPassword': self.currentModal.find('.t3js-createAdmin-password').val(),
            'userPasswordCheck': self.currentModal.find('.t3js-createAdmin-password-check').val(),
            'userSystemMaintainer': (self.currentModal.find('.t3js-createAdmin-system-maintainer').is(':checked')) ? 1 : 0
          }
        },
        cache: false,
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              if (element.severity == 2) {
                Notification.error(element.message);
              }
              else {
                Notification.success(element.title);
              }
            });
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
      self.currentModal.find('.t3js-createAdmin-user').val('');
      self.currentModal.find('.t3js-createAdmin-password').val('');
      self.currentModal.find('.t3js-createAdmin-password-check').val('');
      self.currentModal.find('.t3js-createAdmin-system-maintainer').prop('checked', false);
    }
  };
});
