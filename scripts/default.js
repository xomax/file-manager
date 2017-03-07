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
			.on('click', '.browser button', function(e){
				e.preventDefault();
				var button = $(this);
				var link = button.closest('a');
				if (link.length > 0) {
					$this.openButton(button, link);
				}
			})
			.on('click', '.new-file', function(e){
				e.preventDefault();
				$this.openNewFile();
			})
			.on('click', '.new-folder', function(e){
				e.preventDefault();
				$this.openNewFolder($(this));
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
	openButton: function ($button, $link) {
		console.log($button);
	},
	openNewFile: function () {
		// TODO open additional upload plugin
	},
	openNewFolder: function ($button) {
		var form = $('<form class="new-folder-form" action="" method="post"></form>');
		var input = $('<input type="text" name="name">').appendTo(form);
		var submit = $('<input type="submit" value="Uložit">').appendTo(form);
		var $this = this;
		$button
			.after(form)
			.hide();
		input.focus();
		form.on('submit', function(e){
			e.preventDefault();
			$this.api('new-folder', input.val(), function(){
				form.remove();
				$button.show();
				$this.initFolders();
			});
		});
	},
	openFolder: function ($link) {
		this.unselectFolder();
		this.selectFolder($link);
		this.api('load-folder', $link.attr('href'), function(){
			console.log('loaded');
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