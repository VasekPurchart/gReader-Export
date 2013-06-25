Google Reader Feed History Exporter
===================================
Simple PHP script which downloads all feeds with most available history.

Howto:
------

1. Grab Cookie header information you are using with Google Reader when logged in and put it in `./cookieheader.conf`.
   The cookie data must definitely contain `SID` and `HSID` params.

2. Run script: `php export.php`

3. Profit

If you need to pause, or the script is killed, you can restart it and it will continue from the next feed it did yet not download.
If the downloading is interrupted during downloading a feed, you should delete the whole dir and start again.
