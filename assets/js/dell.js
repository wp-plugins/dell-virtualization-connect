/*!
 * Dell Blog Network Javascript
 */

//Return jQuery back to its owner if it exists
$.noConflict();

//First argument is ender, not jquery, FYI
(function($,win,doc){

  var object = this;
  var baseUrl = 'http://dell.system-11.com';
  var ajaxCallback = 'ec_callback';
  var clientId;
  var division;
  var blogRoll;
  var debug;

  function init() {
    if(typeof _dec != 'undefined') {
      log('found _dec, executing functions...');
      while (_dec.length > 0) {
          var fnArray = _dec.shift();
          push(fnArray);
      }
    }
    else {
      _dec = [];
    }
    _dec.push = push; //Overwrite push functionality to just call the function once the script has loaded fully
  }

  function push(fnArray) {
    var fn = null;
    var async = false;
    var functionName = fnArray.shift();
    //Expose certain functions via the _dec array
    switch (functionName) {
      case 'setBaseUrl':
        fn = setBaseUrl;
        break;
      case 'setClientId':
        fn = setClientId;
        break;
      case 'setDivision':
        fn = setDivision;
        break;
      case 'getFeed':
        fn = getFeed;
        break;
      case 'updateFeed':
        fn = updateFeed;
        break;
      case 'getBlogList':
        fn = getBlogList;
        break;
      case 'logBaseUrl':
        fn = logBaseUrl;
        break;
      case 'enableDebug':
        fn = enableDebug;
        break;
      case 'setBlogStatus':
        fn = setBlogStatus;
        break;
      case 'setBacklinkActive':
        fn = setBacklinkActive;
        break;
      case 'setBlogRoll':
        fn = setBlogRoll;
        break;
      case 'addBlogRoll':
        fn = addBlogRoll;
        break;
      case 'addBlog':
        fn = addBlog;
        break;
      case 'removeBlog':
        fn = removeBlog;
        break;
    }
    if (typeof fn === 'function') {
      fn.apply(object, fnArray);
    }
  }

  function log(obj) {
    if (debug && win.console) {
      console.log(obj);
    }
  }

  //Public functions
  function getFeed(count, showDescriptions) {
    var data = { clientId: clientId, division: division };
    ajax('articles/feed', data, function(resp){
      var blogs = resp.data['blogs'];
      var client = resp.data['client'];
      var output = '<ul>';
      log('Feed Ajax Response');
      log(resp);
      for(var i=0; i<count && i<blogs.length; i++) {
        if('undefined' != typeof blogs[i].currentArticle) {
          output += '<li><strong><a target="_blank" rel="nofollow" href="' + baseUrl + '/click?url=' + encodeURIComponent(blogs[i].currentArticle['link']) + '">' + blogs[i].currentArticle.title + '</a></strong><br>';
          if(showDescriptions) {
            output += '<span class="dec-description">' + trimWords(blogs[i].currentArticle.description,30) + '...</span><br>';
          }
          output += '<span class="dec-posted-by">Posted by <a rel="nofollow" target="_blank" href="' + blogs[i]['url'] + '">' + blogs[i]['title'] + '</a></span></li>';
        }
          
      }
      output += '</ul>';
      if(client.backlinkActive){
          output += '<a href="' + client.backlink + '">' + client.backlinkText + '</a>';
      }
      $('#dell_edu_content').html(output);
    });
  }

  function updateFeed() {
    var data = { clientId : clientId };
    ajax('feed.updatebyclient', data, function(resp) {
      log(['Feed Updated', resp]);
    });
  }

  function getBlogList() {
    var data = { clientId : clientId, division: division };
    ajax('blog/list', data, function(resp) {
      log(['Blog List Ajax Response',resp]);
      var client = resp.data.client;
      var categories = resp.data.categories;
      var output = '';
      for(category in categories) {
        output += '<h3>' + category + '</h3>';
        output += '<ul>';
        for(var i=0; i<categories[category].length; i++){
          var title = categories[category][i].title;
          var description = categories[category][i].description;
          var url = categories[category][i].url;

          if( title == "false" ){
            title = '';
          }
          if( description == "false" ){
            description = '';
          }
          if( url == "false") {
            url = '';
          }

          output += '<li id="blog_' + categories[category][i]._id + '" style="clear:both;"><p style="float:left;margin-right:10px;">';
          if(category == "User Added") {
            output += '<a href="" onclick="javascript:var remblog=confirm(\'Are you sure you want to remove this blog from your list?\'); if(remblog) { _dec.push([\'removeBlog\',\'' + categories[category][i]._id + '\']); return false;}"><i class="icon-remove"></i></a>';
          }
          else {
            output += '<input onclick="_dec.push([\'setBlogStatus\',\'' + categories[category][i]._id + '\']);" type="checkbox" name="blog" id="' + categories[category][i]._id + '" value="' + categories[category][i]._id + '"';
            var checked = ' checked ';
            for(var j=0; j<client.excludes.length; j++){
              if(categories[category][i]._id == client.excludes[j]) {
                checked = '';
              }
            }
            output += checked;
            output += '/>'
          }
          output += '</p><p><strong>' + title + '</strong> - ' + description + '<br />' + url + '</p></li>';
        }
        output += '</ul>';
      }
      output += '<div><p style="float:left;margin-right:10px;">';
      output += '<input onclick="_dec.push([\'setBacklinkActive\']);" type="checkbox" id="dec_backlink_active" name="backlinkstatus" value="active"';
      if(client.backlinkActive == 1){
         output += ' checked ';
      }
      output += '/></p><p><strong>Show Sponsor Link</strong><br />Please help support the development of this plugin by checking this box.</p></div>';
      output += '<button class="button" onclick="_dec.push([\'updateFeed\']);">Save Settings</button>';
      $('#dec_blogs').html(output);
    });
  }

  function setClientId(newClientId) {
    clientId = newClientId;
  }

  function setDivision(newDivision) {
    division = newDivision;
  }

  function setBacklinkActive() {
    //We use the wordpress ajax for this
    var data = { clientId: clientId, backlinkActive : $('#dec_backlink_active').attr('checked') };
    log(['Changing backlink status: ', data]);
    ajax('client/backlinktoggle', data, function(resp){
      log(['Backlink Status Response', resp]);
    });
  }

  function setBlogStatus(blogId) {
    var data = { clientId: clientId, blogId: blogId, include:$('#'+blogId).attr('checked') };
    var debugtext = "Including Blog";
    log(["Toggling blog status for client", blogId, data]);
    ajax('blog/exclude',data, function(resp){
      log(['Blog Status Response', resp]);
    });
  }

  function addBlog() {
    $('.dbn_error').hide();
    $('#edu_connect_btn_addblog').attr('disabled', true);
    var url = $('#edu_connect_text_addblog').val();
    var data = { clientId: clientId, division: division, url: url }
    log(['Adding Bolg: ', data]);
    ajax('client/addblog',data, function(resp){
      log(['Blog Add Response', resp]);
      if(resp.error) {
        $('.dbn_error').text('Error adding blog: ' + resp.error).show();
        $('#edu_connect_btn_addblog').removeAttr('disabled');
      }
      else {
        win.location.reload(true);
      }
    });
  }

  function removeBlog(blogId) {
    var data = { clientId: clientId, blogId: blogId };
    log(["Removing blog for client", blogId, data]);
    ajax('blog/remove',data, function(resp){
      log(['Blog Remove Response', resp]);
      $('#blog_' + blogId).hide();
    });
  }

  function setBlogRoll(newBlogRoll) {
    log(['Setting blog roll', newBlogRoll]);
    blogRoll = newBlogRoll;
  }

  function addBlogRoll() {
    if (blogRoll != null){
      $('#edu_connect_btn_blogroll').attr('disabled', true);
      var data = { clientId: clientId, blogs: blogRoll, division: division };
      log(['Adding BlogRoll: ', data]);
      ajax('client/addblogroll',data, function(resp){
        log(['Blog Add Response', resp]);
        win.location.reload(true);
      });
    }
    else {
      log('No Blog Roll Set...');
    } 
  }

  function setBaseUrl(newUrl) {
    baseUrl = newUrl;
  }

  function enableDebug() {
    debug = true;
  }

  function logBaseUrl() {
    log('Base Url: ' + baseUrl);
  }

  function ajax(actionUrl, data, callback) {
    
    var ajaxSettings;

    //If actionUrl is false, then use the local wp ajax url
    if(actionUrl === false && typeof ajaxurl != 'undefined') {
      ajaxSettings = {
        url: ajaxurl,
        method: 'post',
        type: 'json',
        data: data
      } 
    }
    else {
      var url = baseUrl + '/' + actionUrl;
      ajaxSettings = {
        url: url,
        type:'jsonp',
        jsonpCallback: ajaxCallback,
        data: data,
        success: callback
      }
    }
    $.ajax(ajaxSettings);
  }

  function trimWords(theString, numWords) {
    expString = theString.split(/\s+/,numWords);
    theNewString=expString.join(" ");
    return theNewString;
  }

  init();

})(ender,window,document);