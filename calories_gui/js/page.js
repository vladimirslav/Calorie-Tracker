"use strict";

var SECONDS_IN_A_DAY = 24 * 60 * 60;
var active_user;
var current_meals;

function ShowMessage(text)
{
    $('<div>').text(text).dialog({modal:true});
}

function LoadView(view, data, callback, callback_data)
{
    var jqxhr = $.ajax({
          url: renderer_link,
          crossDomain: true,
          type: "post",
          data: {data: data, page: view}
      })
      .success(function(html) {
        $('#data').fadeOut("fast", function(){
            $('#data').replaceWith(html);
            $('#data').fadeIn("fast");           
            if (typeof callback !== "undefined" && callback !== null) 
            {
                if (typeof callback_data !== "undefined")
                {
                    callback(html, callback_data);
                }
                else
                {
                    callback(html);
                }
            }
        });
      })
      .fail(function(response) {
        ShowMessage(response);
      })
      .always(function() {
        
    });
}

function addZero(i) {
    if (i < 10) {
        i = "0" + i;
    }
    return i;
}

function InitDateField(dateVal, element)
{
    element.val(dateVal);
    element.datepicker({
        defaultDate: dateVal,
        dateFormat: "dd.mm.yy",
        changeMonth: true,
        maxDate: 0,
    });
}

function FillMealDateTime(dateVal, timeVal)
{
    InitDateField(dateVal, $("#date"));       
    $('#time').val(timeVal);
}

function GetTodayDateStr()
{
    var d = new Date();
    return addZero(d.getDate()) + "." + addZero(d.getMonth() + 1) + "." + d.getFullYear();
}

function OnMealViewLoad(json)
{
    var d = new Date();
    var hour = addZero(d.getHours());
    var minute = addZero(d.getMinutes());
    var dateStr = GetTodayDateStr();
    
    FillMealDateTime(dateStr, hour + ":" + minute);
    $("#time").keydown(FilterTimeInput);
    $("#time").keyup(TimeInputPostProcess);
}

function OnEditMeal(html, data)
{
    FillMealDateTime(data.date, data.time);
    $('#text').val(data.text);
    $('#calories').val(data.calories);
}

function OnLoadSettings()
{
    $('#daily_calories').val(active_user.daily_calories);
    $('#name').val(active_user.name);
}

function OnLoadStatisticsFilter()
{
    InitDateField(GetTodayDateStr(), $('#startdate'));
    InitDateField(GetTodayDateStr(), $('#enddate'));
    
    $("#starttime").keydown(FilterTimeInput);
    $("#starttime").keyup(TimeInputPostProcess);
    
    $("#endtime").keydown(FilterTimeInput);
    $("#endtime").keyup(TimeInputPostProcess);
}

function OnTodayMealLoad(json)
{
    current_meals = json.meals;
    LoadView('main', {user:active_user, meals: json.meals});
}

function OnUserLoad(json)
{
    LoadView('userlist', {user:active_user, users: json.users, page_num: json.page, is_last: json.last});
}

function OnMealListLoad(json, owner_id, owner_name)
{
    current_meals = json.meals;
    LoadView('meallist', {user:active_user,
                          meals: json.meals,
                          page_num: json.page,
                          is_last: json.last,
                          owner_name: owner_name,
                          owner_id: owner_id});
}

function RequestMain(element)
{
    var now = new Date();
    var today = addZero(now.getDate()) + "." + addZero(now.getMonth() + 1) + "." + now.getFullYear();
    SendApiRequest('/meals/filter/' + active_user.id + '?startdate=' + today + '&enddate=' + today,
                   'GET',
                   {},
                   OnTodayMealLoad);
}

function SendUserListRequest(page)
{
    SendApiRequest('users/list/20/' + page,
                   'GET',
                   {},
                   OnUserLoad);
}

function SendUserRequest(id, callback)
{
    SendApiRequest('users/' + id,
                   'GET',
                   {},
                   callback);
}

function RequestMeals(owner_id, owner_name, page)
{
    SendApiRequest('meals/list/' + owner_id,
                   'GET',
                   {amount: 20,
                    page: page,
                   },
                   function(json) {
                       OnMealListLoad(json, owner_id, owner_name);
                   });
}

function RequestUserEdit(id, name)
{
    SendApiRequest('/users/' + id,
                   'GET',
                   {},
                   function (json_from_rest_request) {
                        LoadView('settings',
                                 {user:active_user,
                                  settings_id: id,
                                  settings_name: name
                                 },
                                 function()
                                 {
                                    console.log(json_from_rest_request);
                                    $('#roleval').text(json_from_rest_request.user.role);
                                    $('#daily_calories').val(json_from_rest_request.user.daily_calories);
                                    $('#name').val(json_from_rest_request.user.name);
                                 });
                   });
}

function OnDayListLoaded()
{
    $(function() {
        $( "#calendar" ).accordion();
    });
    $('h3').each( function () {
        if ( $(this).data('calories') > active_user.daily_calories )
        {
            $(this).addClass('statistics_calories_exceeded');
            $(this).next('ul').addClass('calories_exceeded');
            
        }
        else
        {
            $(this).addClass('statistics_calories_ok');
            $(this).next('ul').addClass('calories_ok');
        }
    });
}

function OnStatisticsLoad(json)
{
    current_meals = json.meals;
    LoadView('daylist', {user:active_user, meals: json.meals}, OnDayListLoaded);
}

function RequestStatistics(element)
{
    var request_str = '/meals/filter/' + active_user.id + '?startdate=' + $('#startdate').val() + '&enddate=' + $('#enddate').val();
    if ($('#starttime').val().length > 0)
    {
        request_str += '&starttime=' + $('#starttime').val();
    }
    
    if ($('#endtime').val().length > 0)
    {
        request_str += '&endtime=' + $('#endtime').val();
    }

    SendApiRequest(request_str,
                   'GET',
                   {},
                   OnStatisticsLoad);
}

function OnLogin(json)
{
    // get the meals for today
    active_user = json.user;
    RequestMain();
}

function DeleteMeal(meal_element)
{
    var cancel_redirect = meal_element.data('no-redirect');
    SendApiRequest('/meals/' + meal_element.data('meal-id'),
                   'DELETE',
                   {},
                   function() {
                        meal_element.hide('slow', function(){ meal_element.remove(); });
                        if (typeof cancel_redirect != "undefined")
                        {
                            RequestMain();
                        }
                   });
}

function OnRegister(json)
{
    ShowMessage("Registration Successful");
    LoadView('login', {});
}

function OnSelfDelete(json)
{
    LoadView('login', {});
}

function OnSettingsSaved(json)
{
    active_user = json.user;
    ShowMessage("Settings Saved!");
    RequestMain();
}

function OnOtherUserSave()
{
    ShowMessage("User Data Saved!");
    SendUserListRequest(0);
}

function SendApiRequest(link, method, data, successCallback)
{
    console.log(api_link + link);
    var jqxhr = $.ajax({
          url: api_link + link,
          crossDomain: true,
          cache: false,
          xhrFields: {
             withCredentials: true
          },
          type: method,
          dataType: 'json',
          data: data
      })
      .done()
      .success(successCallback)
      .fail(function(json) {
        ShowMessage(json.responseJSON.message + "(" + json.responseJSON.app_code + ")");
      })
      .always(function() {
        //alert( "finished" );
    });
}

function Register(element)
{
    if ($('#confirm_password').val() != $('#password').val())
    {
        ShowMessage('Passwords Do Not Match!');
    }
    else
    {
        SendApiRequest('users', 
                       'POST',
                       {email: $('#email').val(), password: $("#password").val(), name: $('#name').val()},
                       OnRegister);
    }

}

function SaveMeal(is_new, meal_id, owner_id)
{
    // if meal is new - add it  to database under given owner
    // else - update the existing one under given id
    SendApiRequest('meals' + (is_new ? ('/' + owner_id) : ('/' + meal_id)),
                   is_new ? 'POST' : 'PUT',
                   {date: $('#date').val(), 
                    time: $('#time').val(),
                    calories: $("#calories").val(),
                    text: $('#text').val()},
                    function() {
                        if (owner_id == active_user.id)
                        {
                            RequestMain();
                        }
                        else
                        {   
                            SendUserRequest(owner_id, function(json) {
                                RequestMeals(json.user.id,
                                             json.user.name,
                                             0);
                            });
                        }
                    });
}

function SaveSettings(element)
{
    var user_id = $('#settings').data('user-id');
    var data = {
        daily_calories: $('#daily_calories').val(),
        name: $('#name').val()
    };
    
    if ($('#password').length && $('#password').val().length > 0)
    {
        if ($('#password').val() != $('#confirm_password').val())
        {
            ShowMessage('Passwords in both fields must match!');
            return;
        }
        else
        {
            data.password = $('#password').val();
        }
    }

    if ($('#roleval').length ) {
        data.role = $('#roleval').text();
    }
   
    var callback = OnSettingsSaved;
    if (user_id != active_user.id)
    {
        callback = OnOtherUserSave;
    }
    
    SendApiRequest('users/' + user_id, 
                   'PUT',
                    data,
                    callback);
}

function ShowDialog(title, text, option_confirm, confirm_callback)
{   
    var buttonData = {
        Cancel: function() {
            $( this ).dialog( "close" );
        }
    };
    buttonData[option_confirm] = confirm_callback;
    
    $('<div>')
    .attr('title', title)
    .text(text)
    .dialog({
        resizable: false,
        height:200,
        width: 400,
        modal: true,
        buttons: buttonData
    });
}

function DeleteMe(element)
{
    ShowDialog('Really Delete Yourself From The System?', 
               'Your account will be permanently deleted and cannot be recovered. Are you sure?',
               'Yes. Delete Me',
               function() {
                    SendApiRequest('users/' + active_user.id, 
                                   'DELETE',
                                   {},
                                   OnSelfDelete);
                    $( this ).dialog( "close" );
               });
}

function DeleteUser(id, name, element)
{
    ShowDialog('Really Delete User ' + name + '?', 
               'This account will be permanently deleted and cannot be recovered. Are you sure?',
               'Yes, Delete It',
               function() {
                    SendApiRequest('users/' + id, 
                                   'DELETE',
                                   {},
                                   function() {
                                        ShowMessage('user ' + name + ' deleted')
                                        element.hide('slow', function(){ element.remove(); });
                                   });
                    $( this ).dialog( "close" );
               });
}

function DeleteUserFromProfile(id, name)
{
    ShowDialog('Really Delete User ' + name + ' From The System?', 
               'This account will be permanently deleted and cannot be recovered. Are you sure?',
               'Yes, Delete It',
               function() {
                    SendApiRequest('users/' + id, 
                                   'DELETE',
                                   {},
                                   function() {
                                        ShowMessage('user ' + name + ' deleted');
                                        RequestMain();
                                   });
                    $( this ).dialog( "close" );
               });
}

function LoginRequest(element)
{
    SendApiRequest('users/auth', 
                   'POST',
                   {email: $('#email').val(), password: $("#password").val()},
                   OnLogin);
}

function LoadLoginView(element)
{
    LoadView('login', {});
}

function LoadRegisterView(element)
{
    LoadView('register', {});
}

function LoadMealView(element)
{
    LoadView('addmeal', {user:active_user, meal_owner: element.data('owner')}, OnMealViewLoad);
}

function LoadSettingsView(element)
{
    LoadView('settings', {user:active_user}, OnLoadSettings);
}

function SendUserEditRequest(element)
{
    RequestUserEdit(element.closest('li').data('user-id'), element.closest('li').data('username'));
}

function LoadStatisticsFilterView(element)
{
    LoadView('statistics', {user:active_user}, OnLoadStatisticsFilter);
}

function SendDeleteUserRequest(element)
{
        var user_id = element.closest("li").data('user-id');
        var username = element.closest("li").data('username')
        DeleteUser(user_id, username, element.closest("li"));
}

function SendMealsRequest(element)
{
    RequestMeals(element.data('user-id'),
                 element.data('username'),
                 element.data('page'));
}

function DeleteUserRequestSentFromProfile(element)
{
    var user_id = $('#settings').data('user-id');
    var user_name = $('#settings').data('username');
    DeleteUserFromProfile(user_id, user_name);
}

function LoadEditMealView(element)
{
    var meal_id = element.closest("li").data('meal-id');
    var selected_meal = null;
    for (var meal in current_meals)
    {
        if (current_meals[meal].id == meal_id)
        {
            selected_meal = current_meals[meal];
            break;
        }
    }
    LoadView('addmeal', {user:active_user, is_edit:true, meal_id: meal_id}, OnEditMeal, selected_meal);
}

function DeleteMealRequest(element)
{
    DeleteMeal(element.closest("li"));
}

function SaveNewMealRequest(element)
{
    SaveMeal(true, 0, element.data('owner'));
}

function UpdateMealRequest(element)
{
    SaveMeal(false, element.data('meal-id'), element.data('owner'));
}

function RequestUserList(element)
{
    SendUserListRequest(element.data('page'));
}

function SetNewRoleVal(element)
{
    $("#roleval").text(element.text());
    $('#roleselector').click();
}

function LoadAboutPage(element)
{
    LoadView('about', {user:active_user});
}

function LoadResetPasswordView(element)
{
    LoadView('recoverpassword', {});
}

function SendLogoutRequest(element)
{
    SendApiRequest('logout', 
                   'GET',
                   {},
                   function() {
                       active_user = null;
                       current_meals = null;
                       LoadView("login", {});
                   });
}

function SendResetPasswordRequest(element)
{
    SendApiRequest('users/reset', 
                   'PUT',
                   {email: $('#email').val()},
                   function() {
                       ShowMessage('Password Has Been Sent To Your Email!');
                       LoadView("login", {});
                   });
}

var APP_REQUESTS = {
    login: LoginRequest,
    register: Register,
    req_login: LoadLoginView,
    reg_request: LoadRegisterView,
    add_meal: LoadMealView,
    mainpage: RequestMain,
    settings: LoadSettingsView,
    edit_user: SendUserEditRequest,
    statistics: LoadStatisticsFilterView,
    getstatistics: RequestStatistics,
    savesettings: SaveSettings,
    deleteme: DeleteMe,
    delete_user: SendDeleteUserRequest,
    meallist: SendMealsRequest,
    delete_user_from_profile: DeleteUserRequestSentFromProfile,
    edit_meal: LoadEditMealView,
    delete_meal: DeleteMealRequest,
    post_meal: SaveNewMealRequest,
    update_meal: UpdateMealRequest,
    userlist: RequestUserList,
    selectrole: SetNewRoleVal,
    about: LoadAboutPage,
    reset_password: LoadResetPasswordView,
    logout: SendLogoutRequest,
    put_reset_pw: SendResetPasswordRequest,
};

function PrepareRequest(request_type, element)
{
    if (request_type in APP_REQUESTS) 
    {
        APP_REQUESTS[request_type](element);
    }
    else
    {
        ShowMessage('Unknown Request!');
    }
}

$(document).ready(function(){
    $( document ).on( 'click', 'a', function(e) {
        PrepareRequest($(this).data('type'), $(this));
        return false;
    });
    
    $(document).on( 'keypress', 'input', function(e) {
        if(e.which == ENTER_KEYCODE) {
            if ($('#mainaction').length)
            {
                $('#mainaction').click();
            }
        }
    });
    
    LoadView("login", {});
});