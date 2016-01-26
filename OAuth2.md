# Introduction #

An authorization method is a schema the client application uses to gain access to account information. AdWords, DoubleClick AdExchange Buyer and DFP APIs support several authorization methods including ClientLogin, OAuth1.0a and OAuth2.0. If you are still using an email address and a password to access the API, you are using the ClientLogin method which is now [deprecated and is scheduled for sunset](https://developers.google.com/accounts/docs/AuthForInstalledApps).

<a href='http://www.youtube.com/watch?feature=player_embedded&v=KetKPhxY4Js' target='_blank'><img src='http://img.youtube.com/vi/KetKPhxY4Js/0.jpg' width='425' height=344 /></a>

Previously, in a [blog post](http://googleadsdeveloper.blogspot.com/2012/08/oauth-in-ads-apis.html) we've covered general aspects of OAuth2.0 authorization and its benefits. This article will focus on using these methods within the [official PHP client library](http://code.google.com/p/google-api-adwords-php).

# Creating an application identifier #

In order to use the OAuth 2.0 authorization with Google services, you need to create an application identifier and secret (also known as client ID and client secret).

Visit [Google Cloud Console](https://cloud.google.com/console) and:
  1. Create a new project (or use an existing project)
  1. Click the project to open
  1. On the left panel, click on “APIs & auth” to expand the menu, and then click on “Registered apps”
  1. Click on “Register App”
  1. Choose either Web Application or Native application depending on the style of your application
    * If you wish to use our sample code ([GetRefreshToken.php](https://code.google.com/p/google-api-adwords-php/source/browse/examples/AdWords/Auth/GetRefreshToken.php)) to generate a refresh token, then you have to choose Native application
    * If you choose Web Application, you will also need to write your own web application that can complete the OAuth 2.0 flow
  1. Click “Register” to complete the steps;  Client ID and client secret will be available underneath the “OAuth 2.0 Client ID” section (click to expand it if it’s not already expanded).

<img src='https://lh5.googleusercontent.com/-pIBySF-6Zkw/Up4dEDu3b5I/AAAAAAAAAfk/n1ydyPtn4lc/w870-h478-no/screen-for-wiki.png' />

The client ID and client secret values are the parameters you will need in the next step.

# Setting up the client library #

All required settings can be configured via the relevant API configuration file ([AdWords](https://code.google.com/p/google-api-adwords-php/source/browse/src/Google/Api/Ads/AdWords/auth.ini)). You can also pass them as a Hash in the constructor if this is the way you initialize the library.

The required parameters are:
```
[OAUTH2]

; If you do not have a client ID or secret, please create one of type
; "installed application" in the Google API console:
; https://code.google.com/apis/console#access
client_id = "INSERT_OAUTH2_CLIENT_ID_HERE"
client_secret = "INSERT_OAUTH2_CLIENT_SECRET_HERE"
...
```

There is a recommended parameter, the ` refresh_token `, but we recommend running the [GetRefreshToken.php example](https://code.google.com/p/google-api-adwords-php/source/browse/examples/AdWords/Auth/GetRefreshToken.php) to get the correct value. See the [OAuth2.0](https://developers.google.com/accounts/docs/OAuth2InstalledApp) documentation for more details.

# Running OAuth2.0 example #

Each of our [client libraries](https://developers.google.com/adwords/api/docs/clientlibraries) provides a simple example of how to execute a request with OAuth2.0 authentication. For PHP see the [AdWords example](https://code.google.com/p/google-api-adwords-php/source/browse/examples/AdWords/Auth/GetRefreshToken.php) on git. The example requires ‘Installed App’ application type client identification.

Provided the configuration file was set up correctly, you will get an authentication URL and a  prompt for the verification string when running this example from the command line. Copy and paste the auth URL into a browser to obtain the verification string. You will need to log in with your AdWords account credentials.

<img src='https://lh3.googleusercontent.com/-JT98Oob54ro/UGBKTDeCjkI/AAAAAAAAAHM/oU7D0D1nJc0/s716/blog1.png' />

Once you type (or copy) the verification string back to the example, you should see the query result. With this result, you should be able to update your [auth.ini](https://code.google.com/p/google-api-adwords-php/source/browse/src/Google/Api/Ads/AdWords/auth.ini#19) with the correct ` refresh_token `.