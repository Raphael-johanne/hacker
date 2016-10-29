/**
 * Created by rco on 24/10/15.
 */
var hack 		= null;

(function ($) {
    $(document).ready(function() {
    	hack =  new Hack();
    	hack.construct();
    	
    });
}(jQuery));

function Hack(){

	this.baseHackurl 	= null;
	this.url 			= null;
    this.error 			= [];
    /**
     * construct
     * 
     * @return void
     */
    this.construct = function(grid)
    {
    	$('#lunch').click(function(event){
    		
    		event.preventDefault();
    		
    		this.url = $('#url').val();
    		if (!this.validateURL(this.url)) {
    			this.error.push('Incorrect url');
    			return this.showError();
    		}
    		this.url = window.btoa(this.url);
    		this.baseHackurl = $('#base_hack_url').val() + '?url=' + this.url;
    		this.lunchHack();
    		this.getProcessLog();
    	}.bind(this));
    	
    	$('.clean').click(function(event){
    		event.preventDefault();
    		$('#report').html('');
    	});
    	
    },
    
    /**
	 * check_is_hackable_action
 	 * get_how_much_cols_action
  	 * get_tables_name_action
	 * get_cols_name_action
 	 * get_final_data_action
	 */
    this.lunchHack = function()
    {
    	$.ajax({
    		url: this.baseHackurl + '&action=' + 'check_is_hackable_action', 
    		success: function(result) {
    			if (result.success == 1) {
    				
    				this.addNotification('Site is hackable !');
    				this.addNotification('Get nbr cols for this entity');
    				
    				$.ajax({
    		    		url: this.baseHackurl + '&action=' + 'get_how_much_cols_action', 
    		    		success: function(result) {
    		    			if (result.nbr_cols > 0) {
    		    				var nbrCols = result.nbr_cols;
    		    				this.addNotification('We got the nbr col : ' + nbrCols);
    		    				this.addNotification('Get tables name');
    		    				this.addNotification('Call :' + result.url_call);
    		    				
    		    				$.ajax({
    		    		    		url: this.baseHackurl + '&action=get_tables_name_action&nbr_cols='+nbrCols, 
    		    		    		success: function(result) {
    		    		    			
    		    		    			if (result.tables != "") {
    		    		    				
    		    		    				this.addNotification('Tables name should be in the following content');
    		    		    				this.addBreak();
    		    		    				$('#report').append(result.tables);
    		    		    				this.addNotification('Call :' + result.url_call);
    		    		    				var label = $('<label></label>');
    		    		    				label.text('Witch table you want ?');
    		    		    				$('#hack-container').append(label);
    		    		    				$('#hack-container').append('<input id="table_name" type="text"/>');
    		    		    				var a = $('<a></a>')
    		    		    				a.text('Lunch');
		    		    					
		    		    					a.click(function(e){
		    		    						e.preventDefault()
		    		    						var tableName = $('#table_name').val();
		    		    						
		    		    						if (tableName == "") {
			    		    						alert('Please enter a table name');
			    		    					} else {
			    		    						
			    		    						$.ajax({
			    	    		    		    		url: this.baseHackurl + '&action=' + 'get_cols_name_action&table='+tableName+'&nbr_cols='+nbrCols, 
			    	    		    		    		success: function(result) {
			    	    		    		    			if (result.columns != "") {
			    	    		    		    				this.addNotification('Call :' + result.url_call);
			    	    		    		    				this.addNotification('Columns name should be in the following content');
			    	    		    		    				this.addBreak();
			    	    		    		    				$('#report').append(result.columns);
			    	    		    		    				var label = $('<label></label>');
			    	    		    		    				label.text('Witch column you want ?');
			    	    		    		    				$('#hack-container').append(label);
			    	    		    		    				$('#hack-container').append('<input id="column_name" type="text"/>');
			    	    		    		    				var a = $('<a></a>')
			    	    		    		    				a.text('Lunch');
			    	    		    		    				a.click(function(event){
			    	    		    		    					
			    	    		    		    					event.preventDefault();
			    	    		    		    					
			    	    		    		    					var ColumnName = $('#column_name').val();
			    	    		    		    					
			    	    		    		    					if (ColumnName == "") {
			    	    		    		    						alert('Please enter a column name');
			    	    		    		    					} else {
			    	    		    		    						
			    	    		    		    						$.ajax({
			    	    		    	    		    		    		url: this.baseHackurl + '&action=' + 'get_final_data_action&col='+ColumnName+'&table='+tableName+'&nbr_cols='+nbrCols, 
			    	    		    	    		    		    		success: function(result) {
			    	    		    	    		    		    			if (result.content != "") {
			    	    		    	    		    		    				this.addNotification('Call :' + result.url_call);
			    	    		    	    		    		    				this.addNotification('Data asked should be in the following content');
			    	    		    	    		    		    				this.addBreak();
			    	    		    	    		    		    				$('#report').append(result.content);
			    	    		    	    		    		    			} 
			    	    		    	    		    		    			
			    	    		    	    		    		    		}.bind(this)
			    	    		    	    		    		    	});
			    	    		    		    					}
			    	    		    		    				}.bind(this));
			    	    		    		    				$('#hack-container').append(a);
			    	    		    		    				
			    	    		    		    			} 
			    	    		    		    			
			    	    		    		    		}.bind(this)
			    	    		    		    	});
			    		    					}
		    		    						
		    		    					}.bind(this))
    		    		    				$('#hack-container').append(a);
    		    		    			} else {
    		    		    				this.addError('No result founded :(');
    		    		    			} 
    		    		    			 
    		    		    		}.bind(this)
    		    		    	});
    		    			} else {
    		    				this.addError('Cannot got nbr cols :(');
    		    			}
    		    		}.bind(this)
    		    	});
    				
    			} else {
    				this.addError('Site is not hackable');
    			}
    		}.bind(this)
    	});
    },
    
    this.getProcessLog = function()
    {
    	setInterval(function () {
    		$.ajax({
        		url: this.baseHackurl + '&action=' + 'get_log_action', 
        		success: function(result) {
        			if (result.content != "") {
        				this.addNotification('Log :' + result.content);
        			} 
        			
        		}.bind(this)
        	}.bind(this));
        }.bind(this),5000);
    },
    
    this.addBreak = function()
    {
    	$('#report').append('<br /><br />-----------------------------------------------------------------<br /><br />');
    },
    
    this.addNotification = function(message)
    {
    	return this.appendElement('notification', message);
    },
    
    this.addError = function(message)
    {
    	return this.appendElement('error', message);
    },
    
    this.appendElement = function (type, message) 
    {
    	$('#hack-container').append('<p class="'+type+'">'+message+'</p>');
    },
    
    this.showError = function()
    {
    	var html = '';
    	$(this.error).each(function(index, item){
    		html += item + '<br />';
    	});
    	$('#error').html(html);
    	this.error 	= [];
    },
    
    this.validateURL = function(textval) 
    {
        var urlregex = /^(https?|ftp):\/\/([a-zA-Z0-9.-]+(:[a-zA-Z0-9.&%$-]+)*@)*((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[1-9]?[0-9])){3}|([a-zA-Z0-9-]+\.)*[a-zA-Z0-9-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(:[0-9]+)*(\/($|[a-zA-Z0-9.,?'\\+&%$#=~_-]+))*$/;
        return urlregex.test(textval);
    }
};



