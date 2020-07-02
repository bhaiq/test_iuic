<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>pageview</title>
    <meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <script type="text/javascript" src="./charting_library/charting_library.min.js"></script>
    <script type="text/javascript" src="./datafeeds/udf/dist/polyfills.js"></script>
    <script type="text/javascript" src="./datafeeds/udf/dist/bundle.js"></script>
    <style>
        body {
            margin: 0;
        }
    </style>
    <script type="text/javascript">
        function getParameterByName(name) {
            name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
            var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
                results = regex.exec(location.search);
            return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
        }


        TradingView.onready(function () {
            var udf_datafeed = new Datafeeds.UDFCompatibleDatafeed("{{url('market')}}");
            // var udf_datafeed = new Datafeeds.UDFCompatibleDatafeed("http://13.250.27.45:8081/market");
            var widget = window.tvWidget = new TradingView.widget({
                // debug: true, // uncomment this line to see Library errors and warnings in the console
                fullscreen: true,
                symbol: getParameterByName('team_name'),
                interval: 'D',
                toolbar_bg: '#f4f7f9',
                container_id: "tv_chart_container",
                timezone: "Asia/Shanghai",
                //	BEWARE: no trailing slash is expected in feed URL
                datafeed: udf_datafeed,
                library_path: "../charting_library/",
                locale: getParameterByName('lang') || "zh",
                //	Regression Trend-related functionality is not implemented yet, so it's hidden for a while
                drawings_access: { type: 'black', tools: [{ name: "Regression Trend" }] },
                disabled_features: [
                    "save_chart_properties_to_local_storage",
                    "volume_force_overlay",
                    "header_symbol_search",
                    "header_settings",
                    "header_saveload",
                    "header_layouttoggle",
                    "timezone_menu",
                    "header_chart_type",
                    "header_indicators",
                    "header_compare",
                    "header_undo_redo",
                    "header_fullscreen_button",
                    "control_bar",
                    "header_interval_dialog_button",
                    "header_resolutions",
                    "header_screenshot",
                    "display_market_status",
                    "left_toolbar"

                ],
                enabled_features: ["move_logo_to_main_pane", "study_templates", "caption_buttons_text_if_possible"],
                overrides: {
                    // "mainSeriesProperties.style": 3,
                    "symbolWatermarkProperties.color": "#944",
                    "volumePaneSize": "medium",
                    "scalesProperties.lineColor": "#61688A",
                    "scalesProperties.textColor": "#61688A",
                    "paneProperties.background": "#181B2A",
                    "paneProperties.vertGridProperties.color": "#1f2943",
                    "paneProperties.horzGridProperties.color": "#1f2943",
                    "paneProperties.crossHairProperties.color": "#9194A3",
                    "paneProperties.legendProperties.showLegend": !!!0,
                    "paneProperties.legendProperties.showStudyArguments": !0,
                    "paneProperties.legendProperties.showStudyTitles": !0,
                    "paneProperties.legendProperties.showStudyValues": !0,
                    "paneProperties.legendProperties.showSeriesOHLC": !0,
                    "mainSeriesProperties.candleStyle.upColor": "#589065",
                    "mainSeriesProperties.candleStyle.downColor": "#ae4e54",
                    "mainSeriesProperties.candleStyle.drawWick": !0,
                    "mainSeriesProperties.candleStyle.drawBorder": !0,
                    "mainSeriesProperties.candleStyle.borderColor": "#4e5b85",
                    "mainSeriesProperties.candleStyle.borderUpColor": "#589065",
                    "mainSeriesProperties.candleStyle.borderDownColor": "#ae4e54",
                    "mainSeriesProperties.candleStyle.wickUpColor": "#589065",
                    "mainSeriesProperties.candleStyle.wickDownColor": "#ae4e54",
                    "mainSeriesProperties.candleStyle.barColorsOnPrevClose": !1,
                    "mainSeriesProperties.hollowCandleStyle.upColor": "#589065",
                    "mainSeriesProperties.hollowCandleStyle.downColor": "#ae4e54",
                    "mainSeriesProperties.hollowCandleStyle.drawWick": !0,
                    "mainSeriesProperties.hollowCandleStyle.drawBorder": !0,
                    "mainSeriesProperties.hollowCandleStyle.borderColor": "#4e5b85",
                    "mainSeriesProperties.hollowCandleStyle.borderUpColor": "#589065",
                    "mainSeriesProperties.hollowCandleStyle.borderDownColor": "#ae4e54",
                    "mainSeriesProperties.hollowCandleStyle.wickColor": "#C5CFD5",
                    "mainSeriesProperties.haStyle.upColor": "#589065",
                    "mainSeriesProperties.haStyle.downColor": "#ae4e54",
                    "mainSeriesProperties.haStyle.drawWick": !0,
                    "mainSeriesProperties.haStyle.drawBorder": !0,
                    "mainSeriesProperties.haStyle.borderColor": "#4e5b85",
                    "mainSeriesProperties.haStyle.borderUpColor": "#589065",
                    "mainSeriesProperties.haStyle.borderDownColor": "#ae4e54",
                    "mainSeriesProperties.haStyle.wickColor": "#4e5b85",
                    "mainSeriesProperties.haStyle.barColorsOnPrevClose": !1,
                    "mainSeriesProperties.barStyle.upColor": "#589065",
                    "mainSeriesProperties.barStyle.downColor": "#ae4e54",
                    "mainSeriesProperties.barStyle.barColorsOnPrevClose": !1,
                    "mainSeriesProperties.barStyle.dontDrawOpen": !1,
                    "mainSeriesProperties.lineStyle.color": "#4e5b85",
                    "mainSeriesProperties.lineStyle.linewidth": 1,
                    "mainSeriesProperties.lineStyle.priceSource": "close",
                    "mainSeriesProperties.areaStyle.color1": "rgba(122, 152, 247, .1)",
                    "mainSeriesProperties.areaStyle.color2": "rgba(122, 152, 247, .02)",
                    "mainSeriesProperties.areaStyle.linecolor": "#4e5b85",
                    "mainSeriesProperties.areaStyle.linewidth": 1,
                    "mainSeriesProperties.areaStyle.priceSource": "close",
                    // "scalesProperties.lineColor": '#8C9FAD',
                    // "scalesProperties.textColor": '#8C9FAD',
                    // "paneProperties.background": '#ffffff',
                    // "paneProperties.vertGridProperties.color": '#f7f8fa',
                    // "paneProperties.horzGridProperties.color": '#f7f8fa',
                    // "paneProperties.crossHairProperties.color": '#23283D',
                    // "paneProperties.legendProperties.showLegend": !!!0,
                    // "paneProperties.legendProperties.showStudyArguments": !0,
                    // "paneProperties.legendProperties.showStudyTitles": !0,
                    // "paneProperties.legendProperties.showStudyValues": !0,
                    // "paneProperties.legendProperties.showSeriesTitle": !0,
                    // "paneProperties.legendProperties.showSeriesOHLC": !0,
                    // "mainSeriesProperties.candleStyle.upColor": '#03C087',
                    // "mainSeriesProperties.candleStyle.downColor": '#E76D42',
                    // "mainSeriesProperties.candleStyle.drawWick": !0,
                    // "mainSeriesProperties.candleStyle.drawBorder": !0,
                    // "mainSeriesProperties.candleStyle.borderColor": '#C5CFD5',
                    // "mainSeriesProperties.candleStyle.borderUpColor": '#03C087',
                    // "mainSeriesProperties.candleStyle.borderDownColor": '#E76D42',
                    // "mainSeriesProperties.candleStyle.wickUpColor": '#03C087',
                    // "mainSeriesProperties.candleStyle.wickDownColor": '#E76D42',
                    // "mainSeriesProperties.candleStyle.barColorsOnPrevClose": !1,
                    // "mainSeriesProperties.hollowCandleStyle.upColor": '#03C087',
                    // "mainSeriesProperties.hollowCandleStyle.downColor": '#E76D42',
                    // "mainSeriesProperties.hollowCandleStyle.drawWick": !0,
                    // "mainSeriesProperties.hollowCandleStyle.drawBorder": !0,
                    // "mainSeriesProperties.hollowCandleStyle.borderColor": '#C5CFD5',
                    // "mainSeriesProperties.hollowCandleStyle.borderUpColor": '#03C087',
                    // "mainSeriesProperties.hollowCandleStyle.borderDownColor": '#E76D42',
                    // "mainSeriesProperties.hollowCandleStyle.wickColor": '#737375',
                    // "mainSeriesProperties.haStyle.upColor": '#03C087',
                    // "mainSeriesProperties.haStyle.downColor": '#E76D42',
                    // "mainSeriesProperties.haStyle.drawWick": !0,
                    // "mainSeriesProperties.haStyle.drawBorder": !0,
                    // "mainSeriesProperties.haStyle.borderColor": '#C5CFD5',
                    // "mainSeriesProperties.haStyle.borderUpColor": '#03C087',
                    // "mainSeriesProperties.haStyle.borderDownColor": '#E76D42',
                    // "mainSeriesProperties.haStyle.wickColor": '#C5CFD5',
                    // "mainSeriesProperties.haStyle.barColorsOnPrevClose": !1,
                    // "mainSeriesProperties.barStyle.upColor": '#03C087',
                    // "mainSeriesProperties.barStyle.downColor": '#E76D42',
                    // "mainSeriesProperties.barStyle.barColorsOnPrevClose": !1,
                    // "mainSeriesProperties.barStyle.dontDrawOpen": !1,
                    // "mainSeriesProperties.lineStyle.color": '#C5CFD5',
                    // "mainSeriesProperties.lineStyle.linewidth": 1,
                    // "mainSeriesProperties.lineStyle.priceSource": "close",
                    // "mainSeriesProperties.areaStyle.color1": 'rgba(71, 78, 112, 0.1)',
                    // "mainSeriesProperties.areaStyle.color2": 'rgba(71, 78, 112, 0.02)',
                    // "mainSeriesProperties.areaStyle.linecolor": '#C5CFD5',
                    // "mainSeriesProperties.areaStyle.linewidth": 1,
                    // "mainSeriesProperties.areaStyle.priceSource": "close",
                    // "mainSeriesProperties.showPriceLine": true,
                    // "mainSeriesProperties.tooltip.coloc": "#944",
                    // "mainSeriesProperties.showCountdown": true,
                    "paneProperties.legendProperties.showSeriesTitle": false,
                    "paneProperties.topMargin": 40,
                },

                // overrides: {
                //     "mainSeriesProperties.style": 3,
                //     "symbolWatermarkProperties.color": "#944",
                //     "volumePaneSize": "medium",
                //     "scalesProperties.lineColor": '#8C9FAD',
                //     "scalesProperties.textColor": '#8C9FAD',
                //     "paneProperties.background": '#ffffff',
                //     "paneProperties.vertGridProperties.color": '#f7f8fa',
                //     "paneProperties.horzGridProperties.color": '#f7f8fa',
                //     "paneProperties.crossHairProperties.color": '#23283D',
                //     "paneProperties.legendProperties.showLegend": !!!0,
                //     "paneProperties.legendProperties.showStudyArguments": !0,
                //     "paneProperties.legendProperties.showStudyTitles": !0,
                //     "paneProperties.legendProperties.showStudyValues": !0,
                //     "paneProperties.legendProperties.showSeriesTitle": !0,
                //     "paneProperties.legendProperties.showSeriesOHLC": !0,
                //     "mainSeriesProperties.candleStyle.upColor": '#03C087',
                //     "mainSeriesProperties.candleStyle.downColor": '#E76D42',
                //     "mainSeriesProperties.candleStyle.drawWick": !0,
                //     "mainSeriesProperties.candleStyle.drawBorder": !0,
                //     "mainSeriesProperties.candleStyle.borderColor": '#C5CFD5',
                //     "mainSeriesProperties.candleStyle.borderUpColor": '#03C087',
                //     "mainSeriesProperties.candleStyle.borderDownColor": '#E76D42',
                //     "mainSeriesProperties.candleStyle.wickUpColor": '#03C087',
                //     "mainSeriesProperties.candleStyle.wickDownColor": '#E76D42',
                //     "mainSeriesProperties.candleStyle.barColorsOnPrevClose": !1,
                //     "mainSeriesProperties.hollowCandleStyle.upColor": '#03C087',
                //     "mainSeriesProperties.hollowCandleStyle.downColor": '#E76D42',
                //     "mainSeriesProperties.hollowCandleStyle.drawWick": !0,
                //     "mainSeriesProperties.hollowCandleStyle.drawBorder": !0,
                //     "mainSeriesProperties.hollowCandleStyle.borderColor": '#C5CFD5',
                //     "mainSeriesProperties.hollowCandleStyle.borderUpColor": '#03C087',
                //     "mainSeriesProperties.hollowCandleStyle.borderDownColor": '#E76D42',
                //     "mainSeriesProperties.hollowCandleStyle.wickColor": '#737375',
                //     "mainSeriesProperties.haStyle.upColor": '#03C087',
                //     "mainSeriesProperties.haStyle.downColor": '#E76D42',
                //     "mainSeriesProperties.haStyle.drawWick": !0,
                //     "mainSeriesProperties.haStyle.drawBorder": !0,
                //     "mainSeriesProperties.haStyle.borderColor": '#C5CFD5',
                //     "mainSeriesProperties.haStyle.borderUpColor": '#03C087',
                //     "mainSeriesProperties.haStyle.borderDownColor": '#E76D42',
                //     "mainSeriesProperties.haStyle.wickColor": '#C5CFD5',
                //     "mainSeriesProperties.haStyle.barColorsOnPrevClose": !1,
                //     "mainSeriesProperties.barStyle.upColor": '#03C087',
                //     "mainSeriesProperties.barStyle.downColor": '#E76D42',
                //     "mainSeriesProperties.barStyle.barColorsOnPrevClose": !1,
                //     "mainSeriesProperties.barStyle.dontDrawOpen": !1,
                //     "mainSeriesProperties.lineStyle.color": '#C5CFD5',
                //     "mainSeriesProperties.lineStyle.linewidth": 1,
                //     "mainSeriesProperties.lineStyle.priceSource": "close",
                //     "mainSeriesProperties.areaStyle.color1": 'rgba(71, 78, 112, 0.1)',
                //     "mainSeriesProperties.areaStyle.color2": 'rgba(71, 78, 112, 0.02)',
                //     "mainSeriesProperties.areaStyle.linecolor": '#C5CFD5',
                //     "mainSeriesProperties.areaStyle.linewidth": 1,
                //     "mainSeriesProperties.areaStyle.priceSource": "close",
                //     "mainSeriesProperties.showPriceLine": false,
                //     "paneProperties.legendProperties.showSeriesTitle": false,
                //     "paneProperties.legendProperties.showStudyTitles": false,
                // },
                studies_overrides: {

                    // "volume.volume.color.0": "#00FFFF",
                    // "volume.volume.color.1": "#0000FF",
                    // "volume.volume.transparency": 70,
                    // "volume.volume ma.color": "#FF0000",
                    // "volume.volume ma.transparency": 30,
                    // "volume.volume ma.linewidth": 5,
                    // "volume.volume ma.height": 5,
                    // "volume.show ma": true,
                },
                debug: true,
                time_frames: [
                    { text: "1w", resolution: "30" },
                    { text: "7d", resolution: "30" },
                    { text: "5d", resolution: "10" },
                    { text: "3d", resolution: "10" },
                    { text: "2d", resolution: "5" },
                    { text: "1d", resolution: "5" }
                ],
                charts_storage_api_version: "1.1",
                client_id: 'tradingview.com',
                user_id: 'public_user',
                favorites: {
                    intervals: ["1", "15", "60", "240", "1D", "W"],
                    chartTypes: ["Line", "Candles"]
                },
                autosize: true,
                resolution: '1',
                // preset: "black",
                theme: 'Dark'
            });


            widget.onChartReady(function () {
                console.log('=================================')
                console.log(TradingView.version());
                // widget.chart().setChartType(3);
                // widget.chart().setResolution('1');
                // widget.chart().hideAllDrawingTools();
                // console.log(widget.chart().closePopupsAndDialogs());
                console.log('=================================')

                // var ma5 = widget.chart().createStudy("Moving Average", true, false, [5, 'close', 0]);
                // var ma10 = widget.chart().createStudy("Moving Average", true, false, [10, 'close', 0]);
                // var ma30 = widget.chart().createStudy("Moving Average", true, false, [30, 'close', 0]);
                // widget.chart().getStudyById(ma5).setVisible(false);
                // widget.chart().getStudyById(ma10).setVisible(false);
                // widget.chart().getStudyById(ma30).setVisible(false);

                var button1 = widget.createButton()
                    .attr('title', '分时')
                    .text('分时')
                    .on('click', (e) => {
                        widget.chart().setChartType(3);
                        // console.log(ma5);
                        // widget.chart().getStudyById(ma5).setVisible(false);
                        // widget.chart().getStudyById(ma10).setVisible(false);
                        // widget.chart().getStudyById(ma30).setVisible(false);
                        button1.attr('style', 'background-color: #e0e0e0;');
                        button2.attr('style', '');
                        button3.attr('style', '');
                        button4.attr('style', '');
                        button5.attr('style', '');
                        // button6.attr('style', '');
                        widget.chart().setResolution('1', () => { // 1代表1分钟
                            // console.log("set resolution callback");
                        });
                    }).append(() => {

                    });

                var button2 = widget.createButton()
                    .attr('title', '分时')
                    .text('15分钟')
                    .on('click', (e) => {
                        widget.chart().setChartType(1);
                        // widget.chart().getStudyById(ma5).setVisible(true);
                        // widget.chart().getStudyById(ma10).setVisible(true);
                        // widget.chart().getStudyById(ma30).setVisible(true);
                        button1.attr('style', '');
                        button2.attr('style', 'background-color: #e0e0e0;');
                        button3.attr('style', '');
                        button4.attr('style', '');
                        button5.attr('style', '');
                        // button6.attr('style', '');
                        widget.chart().setResolution('15', () => { // 1代表1分钟
                            // console.log("set resolution callback");
                        });
                    }).append(() => {

                    });

                var button3 = widget.createButton()
                    .attr('title', '分时')
                    .text('1小时')
                    .on('click', (e) => {
                        widget.chart().setChartType(1);
                        // widget.chart().getStudyById(ma5).setVisible(true);
                        // widget.chart().getStudyById(ma10).setVisible(true);
                        // widget.chart().getStudyById(ma30).setVisible(true);
                        button1.attr('style', '');
                        button2.attr('style', '');
                        button3.attr('style', 'background-color: #e0e0e0;');
                        button4.attr('style', '');
                        button5.attr('style', '');
                        // button6.attr('style', '');
                        widget.chart().setResolution('60', () => { // 1代表1分钟
                            // console.log("set resolution callback");
                        });
                    }).append(() => {

                    });

                var button4 = widget.createButton()
                    .attr('title', '分时')
                    .text('4小时')
                    .on('click', (e) => {
                        widget.chart().setChartType(1);
                        // widget.chart().getStudyById(ma5).setVisible(true);
                        // widget.chart().getStudyById(ma10).setVisible(true);
                        // widget.chart().getStudyById(ma30).setVisible(true);
                        button1.attr('style', '');
                        button2.attr('style', '');
                        button3.attr('style', '');
                        button4.attr('style', 'background-color: #e0e0e0;');
                        button5.attr('style', '');
                        // button6.attr('style', '');
                        widget.chart().setResolution('240', () => { // 1代表1分钟
                            // console.log("set resolution callback");
                        });
                    }).append(() => {

                    });

                var button5 = widget.createButton()
                    .attr('title', '分时')
                    .text('1日')
                    .on('click', (e) => {
                        widget.chart().setChartType(1);
                        // widget.chart().getStudyById(ma5).setVisible(true);
                        // widget.chart().getStudyById(ma10).setVisible(true);
                        // widget.chart().getStudyById(ma30).setVisible(true);
                        button1.attr('style', '');
                        button2.attr('style', '');
                        button3.attr('style', '');
                        button4.attr('style', '');
                        button5.attr('style', 'background-color: #e0e0e0;');
                        // button6.attr('style', '');
                        widget.chart().setResolution('D', () => { // 1代表1分钟
                            // console.log("set resolution callback");
                        });
                    }).append(() => {

                    });
                /*var button6 = widget.createButton()
                    .attr('title', '分时')
                    .text('1周')
                    .on('click', (e) => {
                        widget.chart().setChartType(1);
                        // widget.chart().getStudyById(ma5).setVisible(true);
                        // widget.chart().getStudyById(ma10).setVisible(true);
                        // widget.chart().getStudyById(ma30).setVisible(true);
                        button1.attr('style', '');
                        button2.attr('style', '');
                        button3.attr('style', '');
                        button4.attr('style', '');
                        button5.attr('style', '');
                        button6.attr('style', 'background-color: #e0e0e0;');
                        widget.chart().setResolution('W', () => { // 1代表1分钟
                            // console.log("set resolution callback");
                        });
                    }).append(() => {

                    });*/

                button5.attr('style', 'background-color: #e0e0e0;');
            });
        });
    </script>
</head>

<body>
<div id="tv_chart_container"></div>
</body>

</html>