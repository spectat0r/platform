var navigation = navigation || {};
navigation.dotMenu = navigation.dotMenu || {};

navigation.dotMenu.MainView = Backbone.View.extend({
    options: {
        el: '.pin-menus .tabbable',
        defaultTabOptions: {
            hideOnEmpty: false
        }
    },
    tabs: {},

    templates: {
        tab: _.template($("#template-dot-menu-tab").html()),
        content: _.template($("#template-dot-menu-tab-content").html()),
        emptyMessage: _.template($("#template-dot-menu-empty-message").html())
    },

    initialize: function() {
        this.$tabsContainer = this.$('.nav-tabs');
        this.$tabsContent = this.$('.tab-content')
        this.$tabsContent.find('.menu-close').click(_.bind(this.close, this));
    },

    addTab: function(options) {
        var data = _.extend(this.options.defaultTabOptions, options);

        data.$tab = this.$('#' + data.key + '-tab');
        if (!data.$tab.length) {
            data.$tab = $(this.templates.tab(data));
            this.$tabsContainer.append(data.$tab);
        }

        data.$tabContent = this.$('#' + data.key + '-content');
        if (!data.$tabContent.length) {
            data.$tabContent = $(this.templates.content(data));
            this.$tabsContent.append(data.$tabContent);
        }

        data.$tabContentContainer = data.$tabContent.find('ul');
        this.tabs[data.key] = data;
    },

    getTab: function(key) {
        return this.tabs[key];
    },

    addTabItem: function(tabKey, item, prepend) {
        if (this.isTabEmpty(tabKey)) {
            this.cleanup(tabKey);
        }
        var el = null;
        if (_.isElement(item)) {
            el = item;
        } else if (_.isObject(item)) {
            if (!_.isFunction(item.render)) {
                item = new navigation.dotMenu.ItemView({model: item});
            }
            el = item.render().$el;
        }

        if (el) {
            if (prepend) {
                this.getTab(tabKey).$tabContentContainer.prepend(el);
            } else {
                this.getTab(tabKey).$tabContentContainer.append(el);
            }
        }
    },

    cleanup: function(tabKey) {
        this.getTab(tabKey).$tabContentContainer.empty();
    },

    checkTabContent: function(tabKey) {
        var isEmpty = this.isTabEmpty(tabKey);
        if (isEmpty) {
            this.hideTab(tabKey);
        } else {
            this.showTab(tabKey);
        }
    },

    isTabEmpty: function(tabKey) {
        var tab = this.getTab(tabKey);
        return !tab.$tabContentContainer.children().length || tab.$tabContentContainer.html() == this.templates.emptyMessage();
    },

    hideTab: function(tabKey) {
        var tab = this.getTab(tabKey);
        if (tab.hideOnEmpty) {
            tab.$tab.hide();
        } else {
            this.getTab(tabKey).$tabContentContainer.html(this.templates.emptyMessage());
        }
    },

    showTab: function(tabKey) {
        this.getTab(tabKey).$tab.show();
    },

    close: function() {
        this.$el.parents('.dropdown').removeClass('open');
    }
});

$(function() {
    window.navigation.dotMenu.MainViewInstance = new navigation.dotMenu.MainView();
});

