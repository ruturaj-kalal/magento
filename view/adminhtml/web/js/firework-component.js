define([
    'jquery',
    'uiComponent',
    'ko',
    'underscore',
    'mage/url',
    'mage/translate'
], function ($, Component, ko, _, urlBuilder) {
    'use strict';

    return Component.extend({
        channelsDropdown: ko.observableArray([]),
        playlistsDropdown: ko.observableArray([]),
        selectedChannel: ko.observable(),
        selectedChannelId: ko.observable(),
        selectedPlaylist: ko.observable(),
        selectedPlaylistId: ko.observable(),
        selectedType: ko.observable(),
        selectionData: ko.observableArray([]),
        selectedVideo: ko.observable(),

        /**
         * Init component
         */
        initialize: function (config) {
            var self = this;
            this._super();
            
            if ($('[name="parameters[data][type]"]').length) {
                self.selectedType($('[name="parameters[data][type]"]').val());
                $('[name="parameters[data][type]"]').change(function () {
                    self.selectedType($(this).val());
                    var channel = self.selectedChannel();
                    self.selectedChannel('');
                    self.selectedChannel(channel);
                });
            }
            this.selectedVideo(self.configValues.video);
            this.initChannels();

            return this;
        },

        initChannels: function () {
            var self = this,
                config = this.channelConfig,
                channels = {},
                playlists = {};

            if (this.validateChannelConfig(config)) {
                channels = config.data.business.channelsConnection.edges;
                _.each(channels, function (channel, key) {
                    var option = {
                        'value': channel.node.username,
                        'label': channel.node.name
                    };
                    self.channelsDropdown.push(option);
                });


                self.selectedChannel.subscribe(function (value) {
                    self.selectionData([]);
                    if (value) {
                        self.selectionData.push({
                            'channel' : value
                        });
                        self.playlistsDropdown([]);
                        _.each(channels, function (channel, key) {
                            if (channel.node.username == value) {
                                self.selectedChannelId(channel.node.id);
                                /* Add default playlist options */
                                self.playlistsDropdown.push({"id": "all", "value": "all", "label": $.mage.__('All video in channel')});
                                if (self.selectedType() == "story_block") {
                                    self.playlistsDropdown.push({"id": "specific", "value": "specific", "label": $.mage.__('A specific video')});
                                }

                                if (typeof channel.node != 'undefined' &&
                                   typeof channel.node.playlistsConnection != 'undefined' &&
                                   typeof channel.node.playlistsConnection.edges != 'undefined' &&
                                   channel.node.playlistsConnection.edges.length
                                ) {
                                    playlists = channel.node.playlistsConnection.edges;
                                    _.each(playlists, function (playlist) {
                                        var option = {
                                            'id'   : playlist.node.id,
                                            'value': playlist.node.id,
                                            'label': playlist.node.displayName
                                        };
                                        self.playlistsDropdown.push(option);
                                    });
                                }
                            }
                        });
                    } else {
                        self.playlistsDropdown([]);
                    }
                });

                self.selectedPlaylist.subscribe(function (value) {
                    if (value) {
                        _.each(self.playlistsDropdown(), function (playlist, key) {
                            if (playlist.id && playlist.value == value) {
                                self.selectedPlaylistId(playlist.id);
                            }
                        });

                        if (self.selectionData().length > 1) {
                            self.selectionData().splice(1, 1);
                        }
                        self.selectionData.push({
                            'playlist' : value
                        });
                    }
                });

                let time = 0;
                self.selectionData.subscribe(function () {
                    clearTimeout(time);
                    time = setTimeout(function () {
                        self.loadVideos();
                    },500);
                });
            }
            this.selectedChannel(self.configValues.channel);
            this.selectedPlaylist(self.configValues.playlist);
        },

        validateChannelConfig: function (config) {
            if (typeof config.data != 'undefined' &&
               typeof config.data.business != 'undefined' &&
               typeof config.data.business.channelsConnection != 'undefined' &&
               typeof config.data.business.channelsConnection.edges != 'undefined' &&
               config.data.business.channelsConnection.edges.length
            ) {
                return true;
            }

            return false;
        },

        loadVideos: function () {
            var self = this;
            new Ajax.Request(self.configValues.videosUrl, {
                parameters: {
                    channel_id: self.selectedChannelId(),
                    playlist_id: self.selectedPlaylistId(),
                    video_id : self.selectedVideo(),
                },

                onSuccess: function (transport) {
                    $('#firework_element_channel .channel_videos').html(transport.responseJSON);
                }.bind(this)
            });
        }
    });
});