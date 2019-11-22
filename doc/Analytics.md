## Analytics : 

You can make different analytics blocks :

   - **browsers** : to display browsers used last week
   - **countries** : to display a map with the location of users
   - **devices** : to display devices used last week
   - **source** :  to display where users are coming from
   - **users** : to display when the users are coming
   - **userWeek** : to display the difference in the number of visitors between this week and the previous
   - **userYear** : to display the difference in the number of visitors between this year and the previous

    dashboard:
        blocks:
            -  class: col-12
               position: top
               roles: [ROLE_ADMIN]
               type:     cms.admin.analytics
               settings:
                   analytics:
                       - devices: ["col-md-4", "2 months ago"]
                   colors: ["rgb(195, 236, 255)","rgb(160, 225, 255)", "rgb(114, 210, 255)", "rgb(063, 194, 255)", "rgb(000, 150, 220)", "rgb(000, 150, 174)"]
                   week_colors: ["rgb(160, 225, 255)", "rgb(000, 150, 220)"]
                   year_colors: ["rgb(160, 225, 255)", "rgb(000, 150, 220)"]
                   users_color: 'rgb(000, 123, 255)'
                   map_color: '#0077ae'
                   map_key: "your_key"

###Configuration API
        
   1°  You need a json file to access the API. Follow [this documentation](https://developers.google.com/analytics/devguides/reporting/core/v4/quickstart/service-php)
   
   2 ° When you have your json file. Save it in var dir of the project.
   
   3° Create a YAML file in config/package with name google-analytics-api : 
        
        google_analytics_api:
            google_analytics_json_key: "%env(resolve:GOOGLE_ANALYTICS_JSON_KEY)%"
            
   4° Create ENV variable with the path of your JSON file :
        
        GOOGLE_ANALYTICS_JSON_KEY=../var/analytics-259608.json
   
   5° Create ENV variable with the Wiew id of the project : 
        
        GOOGLE_ANALYTICS_VIEW_ID=000000000
        
        // 000000000 = profile id that you can find in the analytics URL, p000000000 :
        //https://analytics.google.com/analytics/web/?hl=en&pli=1#management/Settings/a222222222w1111111111p000000000/ 

   6° In the json file, copy the client_email and add it to the granted users of you analytics account
        
        https://analytics.google.com/analytics/web/#/
            -> Administration
            -> Gestion des Utilisateurs
            -> +
   
###Configuration of a block

To display a analytics block add it in analytics list like 'devices'
    
   - Fist parameter is the size. Default is col-12. You have to use [bootstrap grid class](https://getbootstrap.com/docs/4.0/layout/grid/)
   - Seconde paramater is the beginning of the data period. Default is 'first day of january this year'. [Use PHP Relative Formats](https://www.php.net/manual/fr/datetime.formats.relative.php)                       

You can't configure the beginning of the data period for userYeek and userYear.

###General Parameters

   - colors => Colors for Doughnut Chart, array of rgb colors : 
    `["rgb(160, 225, 255)", "rgb(000, 150, 220)""rgb(195, 236, 255)","rgb(160, 225, 255)"]`
   - week_colors => Colors for UserYeek Chart, array 2 of rgb colors. First is for last week data and second for this week data
    
   - year_colors => Colors for UserYear Chart, array 2 of rgb colors. First is for last year data and second for this year data
   
   - users_color => Percent of this color is use for User/Hours Chart, one rgb color 
    `'rgb(000, 123, 255)'`
   
   - map_color => Percent of this color is use for Countries Chart, one Hexa color. 
                  Only this color have to be in hexa format `'#0077ae'`
   
   - map_key => Map API key to use Countries Chart "your-key" 
                [Get Api Key](https://developers.google.com/maps/documentation/javascript/get-api-key#step-1-get-an-api-key), 
                [Enable Api Key](https://cloud.google.com/maps-platform/#get-started)

####The colors must be in rgb format :
   
    rgb(000, 123, 255)
    
   In the case of a value less than 100 you must add 0 in front, like in the example. Do not add spaces or others. 
   
   You can find a color converter at [ColorConverter](https://www.w3schools.com/colors/colors_converter.asp)
