# Active-Directory-IDP-For-Websites
Central single-sign on for non-windows websites. Leveraging Active Directory for user sign in.

# Documentation
See the Wiki page for an overview of how the IDP web server acts as an authentication source for websites.
The IIS IDP website provides single sign-on to uesrs on an active directory domain joined computer. 
In most instances the sign-on will be seamless, which means the user will not need to enter a username 
or password to logon to the company website.

# To Do
DONE: Confirm app secret when a request to verify logon token is recieved
- Create a better install guide
- Create a video to show each step of the logon
- Move all logon token file operations to the powershell DB
- Update the sample web site to periodically check if the logon token is still valid
