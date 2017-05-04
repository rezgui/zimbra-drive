<?php
/**
 * Copyright (C) 2017 ZeXtras S.r.l.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2 of
 * the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License.
 * If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\ZimbraDrive\Service\Test;

use OCA\ZimbraDrive\Service\LogService;
use OCA\ZimbraDrive\Settings\AppSettings;
use OCP\IConfig;

class ZimbraAuthenticationServiceConnectionTest implements Test
{
    /**
     * @var IConfig
     */
    private $config;
    /**
     * @var LogService
     */
    private $logger;
    /**
     * @var AppSettings
     */
    private $appSettings;

    /**
     * @param IConfig $config
     * @param LogService $logger
     * @param AppSettings $appSettings
     */
    public function __construct(IConfig $config, LogService $logger, AppSettings $appSettings)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->appSettings = $appSettings;
    }

    /**
     * @return TestResult
     */
    public function run()
    {
        $connectionResult = $this->zimbraAuthenticationServiceIsConnected();
        if($connectionResult->isIsConnected())
        {
            $message = "Zimbra Drive app can reach Zimbra's authentication page.";
            return new TestOk($this->name(), $message);
        }else
        {
            return new TestKo($this->name(), $connectionResult->getErrorMessage());
        }
    }

    /**
     * @return string
     */
    public function name()
    {
        return "Zimbra authentication page connection test";
    }

    /**
     * @return ConnectionTestResult
     */
    public function zimbraAuthenticationServiceIsConnected()
    {
        $zimbra_url =$this->appSettings->getServerUrl();
        $zimbra_port = $this->appSettings->getServerPort();
        $use_ssl = $this->appSettings->useSSLDuringZimbraAuthentication();
        $trust_invalid_certs = $this->appSettings->trustInvalidCertificatesDuringZimbraAuthentication();

        $url = sprintf(
            "%s://%s:%s",
            "http" . ($use_ssl ? "s" : ""),
            $zimbra_url,
            $zimbra_port
        );

        //open connection
        $ch = curl_init();
        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if ($trust_invalid_certs) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 2);
        }

        $response_info = curl_getinfo($ch);
        $http_code = $response_info["http_code"];

        //close connection
        curl_close($ch);
        $isConnectionOk = false;
        $message='';
        if($http_code === 200)
        {
            $isConnectionOk = true;
        } else
        {
            $ssl_error_code = $response_info["ssl_verify_result"];
            if(!$trust_invalid_certs && $ssl_error_code !== 0)
            {
                $ssl_message = self::SSL_ERROR_CODES[$ssl_error_code];
                $error_message = 'ssl verify result: ' . $ssl_message;
            } else
            {
                $error_message = 'response http code: '. $http_code;
            };
            $message = "Impossible to connect to Zimbra ( " . $error_message . " )";
        }
        return new ConnectionTestResult($isConnectionOk, $message);
    }

    const SSL_ERROR_CODES = array(
        0 => "ok the operation was successful",
        1 => "certificate authority invalid", //not official
        2 => "unable to get issuer certificate",
        3 => "unable to get certificate CRL",
        4 => "unable to decrypt certificate's signature",
        5 => "unable to decrypt CRL's signature",
        6 => "unable to decode issuer public key",
        7 => "certificate signature failure",
        8 => "CRL signature failure",
        9 => "certificate is not yet valid",
        10 => "certificate has expired",
        11 => "CRL is not yet valid",
        12 => "CRL has expired",
        13 => "format error in certificate's notBefore field",
        14 => "format error in certificate's notAfter field",
        15 => "format error in CRL's lastUpdate field",
        16 => "format error in CRL's nextUpdate field",
        17 => "out of memory",
        18 => "self signed certificate",
        19 => "self signed certificate in certificate chain",
        20 => "unable to get local issuer certificate",
        21 => "unable to verify the first certificate",
        22 => "certificate chain too long",
        23 => "certificate revoked",
        24 => "invalid CA certificate",
        25 => "path length constraint exceeded",
        26 => "unsupported certificate purpose",
        27 => "certificate not trusted",
        28 => "certificate rejected",
        29 => "subject issuer mismatch",
        30 => "authority and subject key identifier mismatch",
        31 => "authority and issuer serial number mismatch",
        32 => "key usage does not include certificate signing",
        50 => "application verification failure",
    );
}

