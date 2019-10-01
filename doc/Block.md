BLOCK
======

## Analytics : 

You can make different analytics blocks :

   - **base** : for configuration (*this block is required*)
   - **browsers** : to display browsers used last week
   - **countries** : to display a map with the location of users
   - **devices** : to display devices used last week
   - **source** :  to display where users are coming from
   - **users** : to display when the users are coming
   - **userWeek** : to display the difference in the number of visitors between this week and the previous
   - **userYear** : to display the difference in the number of visitors between this year and the previous

###Configuration
   **General**
   ----------
    class:    col-12
              position: top
              roles: [ROLE_ADMIN]
              type:     cms.admin.analytics
              settings:
                template: "@WebEtDesignCms/block/analytics/<block_name>.html.twig"
                
   
   The colors must be in rgb format :
   
    rgb(000, 123, 255)
    
   In the case of a value less than 100 you must add 0 in front, like in the example. Do not add spaces or others. 
   
   You can find a color converter at [ColorConverter](https://www.w3schools.com/colors/colors_converter.asp) 
   
   ---             
   Some block requires more parameters :

   ---
        
   **Base**
   --------
        [...]
        settings:
            template: "@WebEtDesignCms/block/analytics/base.html.twig"
            client_key: 'YOUR_API_KEY'
            colors: ["#FA5882","#DF013A", "#B40431", "#3B0B17"]
            
   - **client_key** : you need a client api key [Doc](https://console.developers.google.com/apis/credentials?project=bright-practice-132423)
   
   - **colors** : array of the used colors. The format of a color is :
   
    rgb(000, 191, 255)
   

   **UserWeek**
   --------
           [...]
           settings:
               template: "@WebEtDesignCms/block/analytics/userWeek.html.twig"
               week_colors : ["rgb(077, 163, 255)", "rgb(000, 123, 255)"]
               
   - **week_colors** : array of the used colors. The format  is :
      
    ["rgb(077, 163, 255)", "rgb(000, 123, 255)"]
      
   The first color is use for the datas of last week. 
   The second color is use for the datas of this week. 
      
   **UserYear**
  --------
          [...]
          settings:
              template: "@WebEtDesignCms/block/analytics/userYear.html.twig"
              year_colors : ["rgb(077, 163, 255)", "rgb(000, 123, 255)"]
              
   - **year_colors** : array of the used colors. The format  is :
                                                       
    ["rgb(077, 163, 255)", "rgb(000, 123, 255)"]
     
  The first color is use for the datas of last year. 
  
  The second color is use for the datas of this year. 
  
 **Users**
  --------
          [...]
          settings:
              template: "@WebEtDesignCms/block/analytics/users.html.twig"
              users_color: 'rgb(000, 123, 255)'
              
  - **users_color** : color used for the amount of users per hour 
  
 **Countries**
  --------
         [...]
         settings:
             template: "@WebEtDesignCms/block/analytics/countries.html.twig"
             map_key: 'YOUR_MAP_API_KEY'

             
  - **map_key** : you need a Key of MAPS JavaScripts API. [Doc](https://developers.google.com/maps/documentation/javascript/get-api-key#step-1-get-an-api-key)
