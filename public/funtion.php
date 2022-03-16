<?php

    /*  
    返回值:  连接失败  -108
    */ 
    function mysql_Conn(){
        //连接数据库函数
        $server_name = "localhost";//数据库IP:端口  默认3306
        $username = "weixin"; //数据库账号
        $password = "qiao0605";//数据库密码
		$dbname = "weixin"; // 数据库名
     
        // 创建连接
        $conn = mysqli_connect($server_name, $username, $password, $dbname);
     
        // 检测连接
        if (!$conn) {
            $output = array(
                'data' => mysqli_connect_error(), 
                'info' => 'MYSQL数据库连接失败', 		                            //消息提示，客户端常会用此作为给弹窗信息。
                 'code' => -108, //成功与失败的代码，一般都是正数或者负数
                );
            echo(json_encode($output)); 
			exit($output['code']);
        } 

        return $conn;
        
    }

    ////来源：https://www.wxapp-union.com/portal.php?mod=view&aid=2279&utm_source=blog.csdn
		/* 
	 输入值：    string：加密的字符串变量
		
     返回值:     连接失败  -108
    */
    function strdecode($string) {   
        $key = md5('011124');   ///加密秘钥
        $string = base64_decode($string);
        $len = strlen($key);
        $code = '';   
         for ($i = 0; $i < strlen($string); $i++) {  
                $k = $i % $len;   
                $code .= $string [$i] ^ $key [$k];
         }   
         return base64_decode($code);   
    }




    function getcurl($url)    //curl用法 
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        // curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        $tmpInfo = curl_exec($curl);     //返回api的json对象
        //关闭URL请求
        curl_close($curl);
        return $tmpInfo;
    }

    function getOpenId($code) //获取Wx的openID
    {

        //获取ID    https://mp.weixin.qq.com/wxamp/devprofile/get_profile?token=1303764361&lang=zh_CN
        $appid = "wxa31da9cde1c3f4d3";//你的ID
        $appsecret = "09d27be3fee8c040d320faa04a41b46b";//你的密码
        $grant_type = "authorization_code"; //授权（必填）

        // 拼接url
        $url = "https://api.weixin.qq.com/sns/jscode2session?" . "appid=".$appid."&secret=".$appsecret."&js_code=".$code."&grant_type=".$grant_type;
        $tmpInfo=getcurl($url);//curl访问

        $json = json_decode($tmpInfo);//对json数据解码
        $res = get_object_vars($json);
        
        return $res;

    }




    /*
    返回值:  连接失败  -102
                成功    100
            查询失败  -106

    传入值:
            // code: 5分钟临时CODE,
			// action: "login",
            --------------------
			// stuID: 学号
    
    */ 
	function login($openid)  //获取ID 并进行数据库查询
	{	
		
		$stuID  = (trim($_GET['stuID'])); 
        $stuID  = strdecode($stuID);
		
		$conn =  mysql_Conn();//连接数据库
			
		// 检测连接
		if (!$conn){
			//die("连接失败: " . mysqli_connect_error());	
			$info= "连接失败: " . mysqli_connect_error();
			return [$result = NULL,$info,-102]; //返回result 和 info 未查询到用户信息的代码-100
		}
		
		// 使用 sql 查询记录
		//$openid ='oclcY5aHoJVAtcEK5qzBLeoEsU3Q';
		$sql = "SELECT * FROM `myguests` WHERE studentID = '$stuID' AND openid = '$openid'";
		$result = mysqli_query($conn, $sql);
		$result = mysqli_fetch_assoc($result);//影响的结果
		mysqli_close($conn);
		
		
		if ($result!=null) {				  //如果数据库中存在此用户的信息，则不需要重新获取
			$info= "OK";
			$a1=array("login"=>true); 
			$result = array_merge($result,$a1); //添加字段：已注册key regist

			return [$result,$info,100];  //返回result 和 info 正确查询到用户信息的代码200
		}	
		else 
		{	
			$info= "ERR";
			$result=array("studentID"=>NULL,"IDnumber"=>NULL,"name"=>NULL,"telephone"=>NULL,"dormitory"=>NULL,"login"=>false); 
			return [$result,$info,-106]; //返回result 和 info 未查询到用户信息的代码-104
		}
	}
	


    /*
    返回值:  连接失败  -102
                成功  200
              已注册  -909
            查询失败  -104

    传入值:
            // code: 5分钟临时CODE,
			// action: "query",
            --------------------
			// stuID: 学号
    
    */ 
    function query()  //获取ID 并进行数据库查询    
	{	
        $conn =  mysql_Conn();//连接数据库
        // 检测连接
		if (!$conn){
			//die("连接失败: " . mysqli_connect_error());	
			$info= "连接失败: " . mysqli_connect_error();
			return [$result = NULL,$info,-102]; //返回result 和 info 未查询到用户信息的代码-100
		}

        $stuID = (trim($_GET['stuID'])); 
        $sql = "SELECT * FROM `myguests` WHERE studentID = '$stuID'";
        $result = mysqli_query($conn, $sql);
		$result = mysqli_fetch_assoc($result);//影响的结果
        
		mysqli_close($conn);
        
        if ($result!=null) {				  //如果数据库中存在此用户的信息，则返回基本信息

            if(empty($result["openid"])){
                //如果openID为空  表示账户还未注册

                $info= "OK";
                $a2=array("query"=>true); 
                $result = array_merge($result,$a2); //添加字段：未注册key regist
                $a2=array("a"=>"purple","b"=>"orange");
                return [$result,$info,200];     //返回result 和 info 正确查询到用户信息的代码200

            }else{
                //如果openID不为空   表示账户已注测
                $info = "already register";
                $result=array("studentID"=>NULL,"IDnumber"=>NULL,"name"=>NULL,"telephone"=>NULL,"dormitory"=>NULL,"query"=>false); 
                return [$result,$info,-909]; //返回result 和 info 未查询到用户信息的代码-100
            }

		}	
		else 
		{	
			$info = "ERR";
			$result=array("studentID"=>NULL,"IDnumber"=>NULL,"name"=>NULL,"telephone"=>NULL,"dormitory"=>NULL,"query"=>false); 
			return [$result,$info,-104]; //返回result 和 info 未查询到用户信息的代码-100
		}

    }




     /*
    返回值:  连接失败  -102
            成功        300
            返回数据失败 -301
            用户已经注册 -300
            

    传入值:
            // code: 5分钟临时CODE,
            // action: "register",
            ---------------------
            // stuID: 学号,
			// stuName:姓名,
			// phoneNUM:手机号,
			// DormitoryNum:宿舍号,
			// sqlID:ID
    
    */ 
	function regist($openid)  //绑定ID    stuID= 学号
	{	
        //获取GET数据 (加密)
		$stuID  = (trim($_GET['stuID'])); 
        $stuName = (trim($_GET['stuName'])); 
        $phoneNUM = (trim($_GET['phoneNUM'])); 
        $DormitoryNum = (trim($_GET['DormitoryNum'])); 
        $sqlID = (trim($_GET['sqlID'])); 
        //解密数据
        $stuID  = strdecode($stuID);
        $stuName  = strdecode($stuName);
        $phoneNUM  = strdecode($phoneNUM);
        $DormitoryNum  = strdecode($DormitoryNum);
        $sqlID  = strdecode($sqlID);

        

		$conn =  mysql_Conn();//连接数据库
		
		// 检测连接
		if (!$conn){
			//die("连接失败: " . mysqli_connect_error());	
			$info= "连接失败: " . mysqli_connect_error();
			return [$result = NULL,$info,-102]; //返回result 和 info 未查询到用户信息的代码-100
		}
		
        $sql1= "UPDATE MyGuests SET openid='$openid' WHERE studentID='$stuID' AND id= '$sqlID' AND openid IS NULL ";
        mysqli_query($conn, $sql1) or $info= "查询失败";
        $flag=mysqli_affected_rows($conn); //更新成功后，影响行数为 1
        
        

        if ($flag){
            
            
            //// 使用 sql 查询记录 返回所有用户信息
            $sql = "SELECT * FROM `MyGuests` WHERE openid = '$openid' "; //$openid
            $result = mysqli_query($conn, $sql);
		    $result = mysqli_fetch_assoc($result);//影响的结果
            mysqli_close($conn);
            if (!$result) {
                $info="返回数据失败";
                $code = -301;
                $result = null;
                return [$result,$info,$code];
            }

            $info= "插入数据段成功: ";
            $code = 300;
            $a1=array("regist"=>true); 
            $result = array_merge($result,$a1); //添加字段：已注册key regist		
            
        
            return [$result,$info,$code];
               //数据     信息   操作码
           
        }	
        else {
            $info="用户已经注册";
            $code = -300;
            $result=array("regist"=>false); 
            return [$result,$info,$code];
               //数据     信息   操作码
        }

        


		
	}

?>
