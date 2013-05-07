<?php
namespace lib\SMTPEmail;

class W_SMTPEmail{
    
    function sendMail($strToEmail, $strToName, $strSubject, $strMessage)
    {
        $strFromEmail = "name@domail.com";
        $strFromName = "Name";
        
        $smtpServer= "tls://smtp.sendgrid.net";//"tls://smtp.gmail.com";
        $port="465";//"465"; //default//25//587
        $username="username";
        $password="password";
        $timeout = "45"; //typical timeout. try 45 for slow servers
        $localhost = $_SERVER['REMOTE_ADDR']; //requires a real ip
        $newLine = "\r\n"; //var just for newlines
        /* you shouldn't need to mod anything else */
        
        //connect to the host and port
        $smtpConnect = fsockopen($smtpServer, $port, $errno, $errstr, $timeout);
        //echo $errstr." - ".$errno;
        $smtpResponse = fgets($smtpConnect, 4096);
        if(empty($smtpConnect))
        {
            $output = "Failed to connect: $smtpResponse";
            return $output;
        }
        else
            $logArray['connection'] = "Connected to: $smtpResponse";
            
        
        //you have to say HELO again after TLS is started
        fputs($smtpConnect, "HELO $localhost". $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['heloresponse2'] = "$smtpResponse";
        //request for auth login
        
        fputs($smtpConnect,"AUTH LOGIN" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['authrequest'] = "$smtpResponse";
        
        //send the username
        fputs($smtpConnect, base64_encode($username) . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['authusername'] = "$smtpResponse";
        
        //send the password
        fputs($smtpConnect, base64_encode($password) . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['authpassword'] = "$smtpResponse";
        
        //email from
        fputs($smtpConnect, "MAIL FROM: <$strFromEmail>" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['mailfromresponse'] = "$smtpResponse";
        
        //email to
        fputs($smtpConnect, "RCPT TO: <$strToEmail>" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['mailtoresponse'] = "$smtpResponse";
        
        //the email
        fputs($smtpConnect, "DATA" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['data1response'] = "$smtpResponse";
        
        //construct headers
        $headers = "MIME-Version: 1.0" . $newLine;
        $headers .= "Content-type: text/html; charset=iso-8859-1" . $newLine;
        $headers .= "To: $strToName <$strToEmail>" . $newLine;
        $headers .= "From: $strFromName <$strFromEmail>" . $newLine;
        $headers .= "Subject: $strSubject" . $newLine;
        
        //observe the . after the newline, it signals the end of message
        fputs($smtpConnect, "$headers\r\n\r\n$strMessage\r\n.\r\n");
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['data2response'] = "$smtpResponse";
        
        // say goodbye
        fputs($smtpConnect,"QUIT" . $newLine);
        $smtpResponse = fgets($smtpConnect, 4096);
        $logArray['quitresponse'] = "$smtpResponse";
        $logArray['quitcode'] = substr($smtpResponse,0,3);
        fclose($smtpConnect);
        
        //a return value of 221 in $retVal["quitcode"] is a success
        return true;
    }
    
    
    
    
    
}
?>
