/*
 * Copyright 2001, 2012, Oracle and/or its affiliates. All rights reserved.
 * Oracle and Java are registered trademarks of Oracle and/or its
 * affiliates. Other names may be trademarks of their respective owners.
 * UNIX is a registered trademark of The Open Group.
 *
 * This software and related documentation are provided under a license
 * agreement containing restrictions on use and disclosure and are
 * protected by intellectual property laws. Except as expressly permitted
 * in your license agreement or allowed by law, you may not use, copy,
 * reproduce, translate, broadcast, modify, license, transmit, distribute,
 * exhibit, perform, publish, or display any part, in any form, or by any
 * means. Reverse engineering, disassembly, or decompilation of this
 * software, unless required by law for interoperability, is prohibited.
 * The information contained herein is subject to change without notice
 * and is not warranted to be error-free. If you find any errors, please
 * report them to us in writing.
 * U.S. GOVERNMENT END USERS: Oracle programs, including any operating
 * system, integrated software, any programs installed on the hardware,
 * and/or documentation, delivered to U.S. Government end users are
 * "commercial computer software" pursuant to the applicable Federal
 * Acquisition Regulation and agency-specific supplemental regulations.
 * As such, use, duplication, disclosure, modification, and adaptation
 * of the programs, including any operating system, integrated software,
 * any programs installed on the hardware, and/or documentation, shall be
 * subject to license terms and license restrictions applicable to the
 * programs. No other rights are granted to the U.S. Government.
 * This software or hardware is developed for general use in a variety
 * of information management applications. It is not developed or
 * intended for use in any inherently dangerous applications, including
 * applications that may create a risk of personal injury. If you use
 * this software or hardware in dangerous applications, then you shall
 * be responsible to take all appropriate fail-safe, backup, redundancy,
 * and other measures to ensure its safe use. Oracle Corporation and its
 * affiliates disclaim any liability for any damages caused by use of this
 * software or hardware in dangerous applications.
 * This software or hardware and documentation may provide access to or
 * information on content, products, and services from third parties.
 * Oracle Corporation and its affiliates are not responsible for and
 * expressly disclaim all warranties of any kind with respect to
 * third-party content, products, and services. Oracle Corporation and
 * its affiliates will not be responsible for any loss, costs, or damages
 * incurred due to your access to or use of third-party content, products,
 * or services.
 */

/**
 * Copyright (C) 2011 Endeca Technologies, Inc.
 *
 * The use of the source code in this file is subject to the ENDECA
 * TECHNOLOGIES, INC. SOFTWARE TOOLS LICENSE AGREEMENT. The full text of the
 * license agreement can be found in the ENDECA INFORMATION ACCESS PLATFORM
 * THIRD-PARTY SOFTWARE USAGE AND LICENSES document included with this software
 * distribution.
 */

//Search Suggestion Module, specific for typeahead dimension search, implemented as a jQuery Plugin

(
function($)
{
    /**
     *Constructor,
     *@param $ele the Element to enable Dim Search Suggestion
     *@param opts the options to be applied
     */
    $.EndecaSearchSuggestor = function(ele, opts)
    {
        this._active = true;
        this._options = opts;
        this._lastValue = '';
        this._element = ele;
        this._container = $('<div class="' + this._options.containerClass + '"></>');
        this._timeOutId;
        this._hideTimeOutId;
        this._selectedIndex = -1;
        
        var suggestor = this;
        
        //append the container to the current page
        $("#wrap").append(this._container);
        
        /**
         *Capture the keyboard event and dispatch to corresponding handlers. 
         */
        ele.keydown(
                function(e) 
                {
                    switch(e.keyCode) 
                    {
                        case 38: //up, select the previous item
                        {
                            if (suggestor._active) 
                            {
                                suggestor.moveToPrev();
                            } 
                            else 
                            {
                                suggestor.show();
                            }
                            break;
                        }
                        case 40: //down, select the next item
                        {
                            if (suggestor._active) 
                            {
                                if(suggestor._selectedIndex == -1)
                                {
                                    suggestor.moveToFirst();    
                                }
                                else
                                {
                                    suggestor.moveToNext();    
                                }
                            } 
                            else 
                            {
                                suggestor.show();
                            }
                            break;
                        }
                        case 9: //tab, hide the box
                        {
                            //suggestor.hide();
                            suggestor.moveToNext();    
                            break;
                        }
                        case 13: //return, select the highlighted item
                        {
                            if (suggestor._active && suggestor._selectedIndex != -1) 
                            {
                                e.preventDefault();
                                suggestor.selectItem();
                                return false;
                            }
                            break;
                        }
                        case 27: // escape, hide the box
                        {
                            if (suggestor._active) 
                            {
                                suggestor.hide();
                            }
                            break;
                        }
                        default:
                        {
                            //other keys, handle the dim search
                            suggestor.handleRequest();
                        }
                    }
                });
        
        //hide box when lost focus
        //update this to the correct functionality
        
        ele.blur(
                function(e)
                {
                    var hideFunction = function() { suggestor.hide();};
                    suggestor._hideTimeOutId = setTimeout(hideFunction, 200);
                    $(".dimResult", this._container).removeClass("selected");
                }
        );
        
    };
    
    
    /**
     * Move the focus to and highlight the next result Item when user type 
     * arrow up key.
     */
    $.EndecaSearchSuggestor.prototype.moveToPrev = function() 
    {
        if(this._selectedIndex == -1)
        {
            this._selectedIndex = 0;
        }
        else
        {
            if(this._selectedIndex == 0)
            {
                //reach the first one
                return;
            }
            this._selectedIndex--;
        }
        $(".dimResult", this._container).removeClass("selected");
        $($(".dimResult", this._container).get(this._selectedIndex)).addClass("selected");
    };
    
    /**
     * Move the focus to and highlight the previous result Item when user type
     * arrow down key.
     */
    $.EndecaSearchSuggestor.prototype.moveToNext = function() 
    {
        if(this._selectedIndex == -1)
        {
            this._selectedIndex = 0;
        }
        else
        {
            if(this._selectedIndex == $(".dimResult", this._container).size() - 1)
            {  
                //rearch the last one
                return;
            }
            this._selectedIndex++;
            
        }
        
        $(".dimResult", this._container).removeClass("selected");
        $($(".dimResult", this._container).get(this._selectedIndex)).addClass("selected");
    };

    
    /**
     * Move the focus to and highlight the first result Item when user type
     * arrow down key.
     */
    $.EndecaSearchSuggestor.prototype.moveToFirst = function() 
    {
        //alert('in here');
        if(this._selectedIndex = -1)
            this._selectedIndex = 0;
        
        //$(".dimResult", this._container).removeClass("selected");
        //$(".dimResult:first", this._container).focusin();
        $($(".dimResult", this._container).get(this._selectedIndex)).addClass("selected");
    };
    
    /**
     * Select the highlighted item when user click or type enter key
     */
    $.EndecaSearchSuggestor.prototype.selectItem = function() 
    {
        if(this._selectedIndex == -1)
        {
            return;
        }
        
        var url = $("a", $(".dimResult", this._container).get(this._selectedIndex)).attr("href");
        document.location.href = url;
    };
    
    /**
     * Hide the search suggestion box
     */
    $.EndecaSearchSuggestor.prototype.hide = function() 
    {
        this._container.hide();
        this._active = false;
    };
    
    /**
     * Show the search suggestion box
     */
    $.EndecaSearchSuggestor.prototype.show = function() 
    {
        if(this._container.is(":hidden"))
        {
            this.setPosition();
            this._container.show();
            this._active = true;
            this._selectedIndex = -1;
        }
    };
    
    /**
     * Activate the search suggestion box.
     */
    $.EndecaSearchSuggestor.prototype.handleRequest = function() 
    {
        var suggestor = this;
        
        var callback = function()
        { 
            var text = $.trim(suggestor._element.val());
            if(text != suggestor._lastValue)
            {
                if(text.length >= suggestor._options.minAutoSuggestInputLength)
                { 
                    suggestor.requestData();
                }
                else
                {
                    suggestor.hide();
                } 
            }
            suggestor._lastValue = text;
        };
        
        if(this._timeOutId)
        {
            clearTimeout(this._timeOutId);
        }
        this._timeOutId = setTimeout(callback, this._options.delay);
    };
    
    /**
     * Send Ajax to backend service to request data
     */
    $.EndecaSearchSuggestor.prototype.requestData = function() 
    {
        var suggestor = this;  
        
        var response = $.ajax(
                {
                    url:'/typeahead_ajax.php',
                    data:{url: suggestor.composeUrl()},
                    dataType:'json',
                    async:true,
                    success:function(data){
                        suggestor.showSearchResult(data);
                    }
                }
        );
    };
    
    /**
     * Send Ajax to backend service to request data
     */
    $.EndecaSearchSuggestor.prototype.flyoutData = function() 
    {
        var suggestor = this;  
        
        var response = $.ajax(
                {
                    url:'/typeahead_ajax.php',
                    data:{url: suggestor._element.val()},
                    dataType:'json',
                    async:true,
                    success:function(data){
                        suggestor.showSearchResult(data);
                    }
                }
        );
    };    

    /**
     * Search suggestion is search term sensitive. So it will take the search
     * term applied on current page and add it into the Ajax request url.
     */
    $.EndecaSearchSuggestor.prototype.composeUrl = function()
    {
        var url = this._options.autoSuggestServiceUrl;
        
        var searchTerm = $.trim(this._element.val());
        
        
        if (url.indexOf('?') == -1)
        {
            url += '?';
        }
        else
        {
            url += '&';
        }
        
        url += 'Dy=1&collection=' + this._options.collection + '&Ntt=' + searchTerm + '*';
        
        return url;
    };
    
    /**
     * Show the search results in the suggestion box
     */
    $.EndecaSearchSuggestor.prototype.showSearchResult = function(data) 
    {
        var htmlResult = this.processSearchResult(data);
        if(htmlResult != null)
        {
            this._container.html(htmlResult);
            this.bindEventHandler();
            this.show();
        }
        else
        {
            //hide the result box if there is no result
            this.hide();
        }
    };
    
    /**
     * Generate rendering HTML according to data
     */
    $.EndecaSearchSuggestor.prototype.processSearchResult = function(data) 
    {
        var dimSearchResult = null;
        
        var autoSuggestCartridges = data.contents[0].autoSuggest;
        
        //if no data returned, returns null
        if(autoSuggestCartridges == null || autoSuggestCartridges.length == 0)
        {
            //alert("autoSuggestCartridges is null");
            return null;
        }
        
        //find the dim search result in the cartridge list, only consider one cartridge
        //for auto-suggest dimension search.
        for(var j = 0; j < autoSuggestCartridges.length; j++)
        {
            var cartridge = autoSuggestCartridges[j];
            
            if(cartridge['@type'] == "AutoSuggestResults")
            {
                //find dim search result
                dimSearchResult = cartridge;
                break;
            }
        }
        
        if (dimSearchResult != null)
        {
            //alert("dimSearchResult is Not Null");
            return this.generateHtmlContent(dimSearchResult);
        }
        //alert("processSearchResult is null");
        return null;
    };
    
    $.EndecaSearchSuggestor.prototype.generateHtmlContent = function(dimSearchResult) 
    {
        var finalContent = null;
        var newContent = null;
        var productContent = null;
        var storeContent = null;

        
        //Contains dimension search results
        //if(dimSearchResult != null && dimSearchResult.dimensionSearchGroups.length > 0)
        if(dimSearchResult != null)
        {            
            //newContent = $('<div></div>');
            newContent = $('<ul></ul>');
            
            //add title if it is not empty
            if(dimSearchResult.title && $.trim(dimSearchResult.title) != "")
            {
                //newContent.append('<div class="title">' + dimSearchResult.title + '</div>');                   
            }
            
            var dimSearchGroupList = dimSearchResult.autoSuggestSearchGroups;
            var k = 0;
            
            //Get Brand 
            
            for(var i = 0; dimSearchGroupList != null && i < dimSearchGroupList.length; i++)
            {
                var dimResultGroup = dimSearchGroupList[i];
                
                //output dimension name here
                //Dimension Name here - Either Dimension or Product to differentiate the items
                var displayName = dimResultGroup.displayName;
                //alert(displayName);
                //newContent.append('<div class="dimRoots">' + displayName + '</div>');               
                
                //output dim result of this group here
                if(displayName == 'Dimesnion search')
                {
                    for(var j = 0; j < dimResultGroup.autoSuggestSearchValues.length; j++)
                    { 
                        var dimResult = dimResultGroup.autoSuggestSearchValues[j];

                        var action = dimResult.contentPath + dimResult.navigationState;
                        var text = dimResult.label;

                        //var link = dimResult.label.replace(/\s/g, '+').toLowerCase();
                        //URL Format -> [Brand-Name]+[Category-Name]
                        //Current URL Format -> + to separate items and - to separate spaces with each item
                        //Future URL Format -> - to separate everything
                        
                        //build up link
                        var link = '';
                        if(dimResult.brand != null) {
                            //link = dimResult.brand.replace(/[^a-z0-9\s\'\&]/gi,'').replace(/[_\s]/g,'-').toLowerCase();
                            //link = dimResult.brand.replace("'",'').replace(/[\W_&&[^\u00C0-\u00FF]]+]/,'-').replace(/^[\s\-]+|[\s\-]+/g, "").toLowerCase();
                            //link = dimResult.brand.replace("'",'').replace(/[\W_&&[^\u00C0-\u00FF]]+]/g,'-').replace(/^[\s\-]+|[\s\-]+/g, "").toLowerCase();
                            link = dimResult.brand.replace("'",'').replace(/[\W|_]/gi,'-').replace(/\-+/g,'-').toLowerCase();
                        }
                        
                        if(dimResult.category != null) {
                            if(dimResult.brand != null){
                                link = link + '+' + dimResult.category.replace(/[^a-z0-9\s\'\&]/gi,'').replace(/[_\s]/g,'-').toLowerCase();
                            }
                            else {
                                link = dimResult.category.replace(/[^a-z0-9\s\&]/gi,'').replace(/[_\s]/g,'-').toLowerCase();
                            }
                            
                        }
                        
                        var ancestors = dimResult.ancestors;
                        var count = dimResult.count == null ? '' : '&nbsp;('+dimResult.count+')';
                        
                        var ancestorsStr = "";
                        if(ancestors != null && ancestors.length > 0)
                        {
                            for(var n = 0; n < ancestors.length; n++)
                            {
                            //    ancestorsStr += ancestors[n].label + " > ";
                            }
                        }
                        if(dimResult.brand != null && dimResult.category != null) {
                            newContent.append('<li class="dimResult"><a href="/search?s=' + link +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '</li>');
                        } else if(dimResult.category != null) {
                            newContent.append('<li class="dimResult"><a href="/search/' + link +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '</li>');  
                        } else {
                            newContent.append('<li class="dimResult"><a href="/search?s=' + link +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '</li>');                            
                        }
                        /*
                        if(dimResult.brand != null && dimResult.category != null) {
                            newContent.append('<div class="dimResult"><div class="link"><a href="/search?s=' + link +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '<div></div>');
                        } else if(dimResult.category != null) {
                            newContent.append('<div class="dimResult"><div class="link"><a href="/search/' + link +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '<div></div>');  
                        } else {
                            newContent.append('<div class="dimResult"><div class="link"><a href="/search?s=' + link +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '<div></div>');                            
                        }
                        */
                    }// for DSV
                } else if (displayName == 'Product search')
                {
                    for(var j = 0; j < dimResultGroup.autoSuggestSearchValues.length; j++)
                    { 
                        var dimResult = dimResultGroup.autoSuggestSearchValues[j];

                        var action = dimResult.contentPath + dimResult.navigationState;
                        var text = dimResult.label;
                        var count = dimResult.count == null ? '' : '&nbsp;('+dimResult.count+')';

                        var ancestorsStr = "";
                        newContent.append('<li class="dimResult" style="text-transform:capitalize;"><a href="' + dimResult.detailURL +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '</li>');
                        /*
                        newContent.append('<div class="dimResult" style="text-transform:capitalize;"><div class="link"><a href="' + dimResult.detailURL +'">' 
                                + ancestorsStr + this.highlightMatched(text) + '</a>' +count+ '<div></div>');
                        */
                    }// for DSV
                }

            } // for DSG
            $('.dimResult',newContent).wrapAll('<ul id="category-brand"></ul>');

/*        
            //TOP PRODUCTS
            if (dimSearchResult.topProductResultGroup != null) {
                var topProductResultsList = dimSearchResult.topProductResultGroup.topProductList;
                if (topProductResultsList != null && topProductResultsList.length > 0) {
                    
                    productContent = $('<div />');
                    for(var i = 0; i < topProductResultsList.length; i++)
                    {
                        var topProductResult = topProductResultsList[i];
                        var displayName = topProductResult.displayName;                

                       productContent.append('<div class="dimResult">' 
                                + '<a href="' + topProductResult.detailURL + '" title="'+ topProductResult.brand + ' ' + topProductResult.displayName + '">'
                                    + '<img src="' + topProductResult.thumbnail + '" title="'+ topProductResult.brand + ' ' + topProductResult.displayName + '" />'
                                    + '<h6>' + topProductResult.displayName + '</h6>'
                                    + '</a>'
                                + '<p>' + topProductResult.displayPrice + '</p>'
                            + '</div>');
                        //);
                    }//end for
                    
                    $('.dimResult', productContent).wrapAll('<div id="top-selling" />');
                    $('#top-selling',productContent).prepend('<div class="dimRoots">Top Selling Products</div>');
                }
            }//end top product
            
            
            //STORE RESULTS
            var storeResultsList = dimSearchResult.storeResults;
            if (storeResultsList != null && storeResultsList.length > 0) {
                storeContent = $('<div />');
                for(var i = 0; i < storeResultsList.length; i++) {
                    var storeResult = storeResultsList[i];
                    var displayName = storeResult.displayName.replace(/"/g, '');
                    var address = storeResult.address.replace(/"/g, '');
                    var city = storeResult.city.replace(/"/g, '');
                    storeContent.append('<div class="dimResult">'
                                            + '<a href="' + storeResult.url + '">'
                                                + '<h6>' + displayName + '</h6>'
                                            + '</a>'
                                            + '<p>' + address + '</p>'
                                            + '<p>' + city + ', ' + storeResult.zip + '</p>'
                                            + '<p>' + 'Phone: ' + storeResult.phone + '</p>'
                                        + '</div>');
                }

                $('.dimResult', storeContent).wrapAll('<div id="store-suggestions" />');
                $('#store-suggestions',storeContent).prepend('<div class="dimRoots">Golfsmith Stores</div>');
            }//end store
*/        
        }// if results

        //has result, return the generated html 
        if(newContent != null)
            finalContent = newContent.html();
        if(productContent != null)
            finalContent = finalContent + productContent.html();
        if(storeContent != null)
            finalContent = finalContent + storeContent.html();

        if(finalContent != null)
        {
            return finalContent;
        }
        
        return null;
    };
    
    /**
     * Highlight the matched text in result item.
     */
    $.EndecaSearchSuggestor.prototype.highlightMatched = function(text)
    {
        var inputText = $.trim(this._element.val()).toLowerCase();
        var highlighted = text.toLowerCase();
        if(highlighted.indexOf(inputText) != -1)
        {
            var index = highlighted.indexOf(inputText);
            var prefix = text.substring(0, index);
            var suffix = text.substring(index + inputText.length);
            inputText = text.substr(index, inputText.length);
            highlighted = prefix + '<span>' + inputText + '</span>' + suffix;
        }
        return highlighted;
    };
    
    /**
     * Bind event handlers for the links and divs in the box
     */
    $.EndecaSearchSuggestor.prototype.bindEventHandler = function()
    {
        var suggestor = this;
        /*
        //test ajax part 2 call
        $("#category-brand.dimResult",this._container).hover(
            function(e)
            {

            }
        );
        */
        //change CSS class when mouseover on result item
        $(".dimResult", this._container).mouseover(
                function(e)
                {
                    $(".dimResult", suggestor._container).removeClass("selected");
                    $(this).addClass("selected");
                    suggestor._selectedIndex = $(".dimResult", suggestor._container).index($(this));
                }
        );
        
        //select the result item when user lick on it
        $(".dimResult", this._container).click(
                function(e)
                {
                    suggestor.selectItem();
                }
        );
        
        //select the result item when user lick on it
        $("a", $(".dimResult", this._container)).click(
                function(e)
                {
                    e.preventDefault();
                    suggestor.selectItem();
                }
        );
        
        //Dim roots are not link, when click, move the focus back to input box
        /*
        $(".dimRoots", this._container).click(
                function()
                {
                    clearTimeout(suggestor._hideTimeOutId);
                    suggestor._element.focus();
                }
        );
        */
    };
    
    /**
     * Set the search suggestion box position
     */
    $.EndecaSearchSuggestor.prototype.setPosition = function()
    {
        var offset = this._element.offset();
        this._container.css({
            top: "110px",
            left: "260px",
            width: "275px"
        });
    };
    //top: offset.top + this._element.outerHeight(),
    
    /**
     * Main function to enable the search suggestion to the selected element.
     */
    $.fn.endecaSearchSuggest = function(options)
    {
        var opts = $.extend({}, $.fn.endecaSearchSuggest.defaults, options);
        
        this.each(
                function()
                {
                    var element = $(this);
                    new $.EndecaSearchSuggestor(element, opts);
                }
        ); 
    };
    
    /**
     * Default settings for the search suggestion.
     */
    $.fn.endecaSearchSuggest.defaults = {
            minAutoSuggestInputLength: 3,
            displayImage: false,
            delay: 0,
            autoSuggestServiceUrl: '',
            collection: '',
            searchUrl: '',
            containerClass: 'dimSearchSuggContainer',
            defaultImage:'no_image.gif'
    };
} 
)(jQuery);
