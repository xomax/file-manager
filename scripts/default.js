var doc = $(document);

var FileManager = {
	selectedFolder: null,
	initDefaults: function () {
		var tree = $('.folders a');
		if (tree.length > 0) {
			this.selectFolder(tree.eq(0));
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
				$this.openNewFolder();
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
	openNewFolder: function () {
		// TODO
	},
	openFolder: function ($link) {
		this.unselectFolder();
		this.selectFolder($link);
		// TODO load browser
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
	}
};

doc.ready(function () {
	FileManager.initDefaults();
	FileManager.initEvents(doc);
});