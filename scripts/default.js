var doc = $(document);

var FileManager = {
	selectedFolder: null,
	initDefaults: function () {
		this.initFolders();
	},
	initFolders: function () {
		var tree = $('.folders a');
		if (tree.length > 0) {
			this.openFolder(tree.eq(0));
		}
	},
	initEvents: function (doc) {
		var $this = this;
		doc
			.on('click', '.browser figure', function(e){
				e.preventDefault();
				var link = $(this).closest('a');
				if (link.length > 0) {
					$this.openDetail(link);
				}
			})
			.on('click', '.delete-file', function(e){
				e.preventDefault();
				var link = $(this).closest('a');
				if (link.length > 0) {
					$this.deleteFile(link);
				}
			})
			// .on('click', '.browser button', function(e){
			// 	e.preventDefault();
			// 	var button = $(this);
			// 	var link = button.closest('a');
			// 	if (link.length > 0) {
			// 		$this.openButton(button, link);
			// 	}
			// })
			.on('click', '.new-folder', function(e){
				e.preventDefault();
				$this.openNewFolder($(this));
			})
			.on('click', '.delete-folder', function(e){
				e.preventDefault();
				$this.openDeleteFolder($(this));
			})
			.on('click', '.folders a', function(e){
				e.preventDefault();
				$this.openFolder($(this));
			});
	},
	openDetail: function ($link) {
		var link = $link.attr('href');
		if (link != '') {
			var detail = $('<div class="image-detail"></div>').appendTo($('.browser'));
			var image = $('<img src="'+link+'">')
				.appendTo(detail)
				.on('click', function () {
					detail.remove();
				});
		}
	},
	deleteFile: function ($link) {
		var link = $('figcaption', $link).text();
		if (link != '') {
			var $this = this;
			console.log('delete');
			$.confirm({
				theme: 'material',
				title: 'Potvrdit smazání souboru',
				content: 'Skutečně chcete smazat soubor <strong>'+link+'</strong>?',
				keyboardEnabled: true,
				buttons: {
					confirm: {
						text: 'Smazat',
						btnClass: 'btn-primary',
						action: function(){
							$this.api('delete-file', link, function(){
								$this.loadFolder();
							});
						}
					},
					cancel: {
						text: 'Ne'
					}
				}
			});
		}
	},
	openButton: function ($button, $link) {
		console.log($button);
	},
	openNewFolder: function ($button) {
		var form = $('<form class="new-folder-form" action="" method="post"></form>');
		var input = $('<input type="text" name="name">').appendTo(form);
		var $this = this;
		$.confirm({
			title: 'Nový adresář',
			content: form,
			buttons: {
				formSubmit: {
					text: 'Založit',
					btnClass: 'btn-blue',
					action: function () {
						var name = this.$content.find('input[name="name"]').val();
						if(!name){
							$.alert('provide a valid name');
							return false;
						}
						$this.api('new-folder', name, function(){
							$this.initFolders();
						});
					}
				},
				cancel: {
					text: 'Zpět'
				}
			},
			onContentReady: function () {
				input.focus();
				// bind to events
				var jc = this;
				this.$content.find('form').on('submit', function (e) {
					// if the user submits the form by pressing enter in the field.
					e.preventDefault();
					jc.$$formSubmit.trigger('click'); // reference the button and click it
				});
			}
		});
	},
	openDeleteFolder: function ($button) {
		var $this = this;
		$.confirm({
			theme: 'material',
			title: 'Potvrdit smazání adresáře',
			content: 'Skutečně chcete smazat adresář <strong>'+this.selectedFolder.text()+'</strong>?',
			keyboardEnabled: true,
			buttons: {
				confirm: {
					text: 'Smazat',
					btnClass: 'btn-primary',
					action: function(){
						$this.api('delete-folder', null, function () {
							$this.initFolders();
						});
					}
				},
				cancel: {
					text: 'Ne'
				}
			}
		});
	},
	openFolder: function ($link) {
		this.unselectFolder();
		this.selectFolder($link);
		this.loadFolder();
	},
	loadFolder: function () {
		var link = $('a', this.selectedFolder).attr('href');
		var $this = this;
		this.api('load-folder', link, function () {
			$(".new-file").dropzone({
				url: "?action=upload-file",
				init: function() {
					this
						.on("success", function(file) {
							$this.loadFolder();
						})
						.on("sending", function(file, xhr, data) {
							data.append("parent", $this.getSelectedFolderLink());
						});
				}
			});
		});
	},
	unselectFolder: function () {
		if (this.selectedFolder != null) {
			this.selectedFolder.removeClass('selected');
			this.selectedFolder = null;
		}
	},
	selectFolder: function ($link) {
		this.selectedFolder = $link.parent();
		this.selectedFolder.addClass('selected');
		this.checkFolderButtons($link.attr('href'));
	},
	checkFolderButtons: function (currentFolderLink) {
		var depth = currentFolderLink.split("/").length;
		if (currentFolderLink == '') {
			depth = 0;
		}
		if (depth == 0) {
			$('button.delete-folder').hide();
			$('button.new-folder').show();
		} else if (depth > 2) {
			$('button.delete-folder').show();
			$('button.new-folder').hide();
		} else {
			$('button.delete-folder').show();
			$('button.new-folder').show();
		}
	},
	api: function (action, value, callback) {
		$.ajax({
			url: '?action='+action,
			method: 'post',
			dataType: 'json',
			data: {
				value: value,
				parent: this.getSelectedFolderLink()
			}
		}).done(function(payload){
			if (typeof payload.snippet != 'undefined') {
				$.each(payload.snippet, function(key, content){
					var box = $('#snippet-'+key);
					if (box.length > 0) {
						box.html(content);
					}
				});
			}
			if ($.isFunction(callback)) {
				callback(payload);
			}
		});
	},
	getSelectedFolderLink: function () {
		if (this.selectedFolder != null) {
			return $('a', this.selectedFolder).attr('href');
		}
		return null;
	}
};

doc.ready(function () {
	FileManager.initDefaults();
	FileManager.initEvents(doc);
});