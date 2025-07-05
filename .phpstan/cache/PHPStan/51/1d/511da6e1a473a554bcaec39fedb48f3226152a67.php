<?php declare(strict_types = 1);

// odsl-/Users/md/code/MarcelloDuarte/Papi/papi-core/src
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v1',
   'data' => 
  array (
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Connection.php' => 
    array (
      0 => '269f4f39b8a1cc0cdc77b1561b127ce8602df621',
      1 => 
      array (
        0 => 'papi\\core\\connection',
      ),
      2 => 
      array (
        0 => 'papi\\core\\__construct',
        1 => 'papi\\core\\getid',
        2 => 'papi\\core\\getsourcenode',
        3 => 'papi\\core\\gettargetnode',
        4 => 'papi\\core\\getsourceoutput',
        5 => 'papi\\core\\gettargetinput',
        6 => 'papi\\core\\settransform',
        7 => 'papi\\core\\gettransform',
        8 => 'papi\\core\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Tools/MathTool.php' => 
    array (
      0 => 'f935a12ee39616197c1d8ce32bcdafd8cff81730',
      1 => 
      array (
        0 => 'papi\\core\\tools\\mathtool',
      ),
      2 => 
      array (
        0 => 'papi\\core\\tools\\getname',
        1 => 'papi\\core\\tools\\getdescription',
        2 => 'papi\\core\\tools\\getparameters',
        3 => 'papi\\core\\tools\\execute',
        4 => 'papi\\core\\tools\\validate',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Tools/ToolInterface.php' => 
    array (
      0 => 'f2455429c93c249de6eb0e8255883593990ca889',
      1 => 
      array (
        0 => 'papi\\core\\tools\\toolinterface',
      ),
      2 => 
      array (
        0 => 'papi\\core\\tools\\getname',
        1 => 'papi\\core\\tools\\getdescription',
        2 => 'papi\\core\\tools\\getparameters',
        3 => 'papi\\core\\tools\\execute',
        4 => 'papi\\core\\tools\\validate',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Tools/HttpTool.php' => 
    array (
      0 => '4d107c4dfdc84b3357b79d407ee0113d32fd6be1',
      1 => 
      array (
        0 => 'papi\\core\\tools\\httptool',
      ),
      2 => 
      array (
        0 => 'papi\\core\\tools\\getname',
        1 => 'papi\\core\\tools\\getdescription',
        2 => 'papi\\core\\tools\\getparameters',
        3 => 'papi\\core\\tools\\execute',
        4 => 'papi\\core\\tools\\validate',
        5 => 'papi\\core\\tools\\formatheaders',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Execution.php' => 
    array (
      0 => 'c59b855840ee35aa065575560244addf1a58a338',
      1 => 
      array (
        0 => 'papi\\core\\execution',
      ),
      2 => 
      array (
        0 => 'papi\\core\\__construct',
        1 => 'papi\\core\\getid',
        2 => 'papi\\core\\getworkflowid',
        3 => 'papi\\core\\getstatus',
        4 => 'papi\\core\\getinputdata',
        5 => 'papi\\core\\getoutputdata',
        6 => 'papi\\core\\setoutputdata',
        7 => 'papi\\core\\getnoderesults',
        8 => 'papi\\core\\addnoderesult',
        9 => 'papi\\core\\geterrormessage',
        10 => 'papi\\core\\seterrormessage',
        11 => 'papi\\core\\getstartedat',
        12 => 'papi\\core\\getcompletedat',
        13 => 'papi\\core\\complete',
        14 => 'papi\\core\\getduration',
        15 => 'papi\\core\\getoutput',
        16 => 'papi\\core\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Agents/AIAgent.php' => 
    array (
      0 => '0e15548366b78087ea910d2efc2d3754951410a0',
      1 => 
      array (
        0 => 'papi\\core\\agents\\aiagent',
      ),
      2 => 
      array (
        0 => 'papi\\core\\agents\\execute',
        1 => 'papi\\core\\agents\\setmodel',
        2 => 'papi\\core\\agents\\setsystemprompt',
        3 => 'papi\\core\\agents\\addtool',
        4 => 'papi\\core\\agents\\setopenaiclient',
        5 => 'papi\\core\\agents\\buildcontext',
        6 => 'papi\\core\\agents\\formatinput',
        7 => 'papi\\core\\agents\\formattools',
        8 => 'papi\\core\\agents\\callllm',
        9 => 'papi\\core\\agents\\processtoolcalls',
        10 => 'papi\\core\\agents\\findtool',
        11 => 'papi\\core\\agents\\addtomemory',
        12 => 'papi\\core\\agents\\clearmemory',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Integrations/Output/EchoNode.php' => 
    array (
      0 => '3af0418b005d60dfbc1e8b6b75ec42b43d134da1',
      1 => 
      array (
        0 => 'papi\\core\\integrations\\output\\echonode',
      ),
      2 => 
      array (
        0 => 'papi\\core\\integrations\\output\\execute',
        1 => 'papi\\core\\integrations\\output\\formatoutput',
        2 => 'papi\\core\\integrations\\output\\formatasjson',
        3 => 'papi\\core\\integrations\\output\\formatasxml',
        4 => 'papi\\core\\integrations\\output\\formatastext',
        5 => 'papi\\core\\integrations\\output\\arraytoxml',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Integrations/OpenAIClient.php' => 
    array (
      0 => '7d38dd9e9e42f45661e5a58b7b9d0d651b2257a2',
      1 => 
      array (
        0 => 'papi\\core\\integrations\\openaiclient',
      ),
      2 => 
      array (
        0 => 'papi\\core\\integrations\\chat',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Integrations/Http/HttpNode.php' => 
    array (
      0 => 'ccd91918f7473558e4c389a398eff143971c0b52',
      1 => 
      array (
        0 => 'papi\\core\\integrations\\http\\httpnode',
      ),
      2 => 
      array (
        0 => 'papi\\core\\integrations\\http\\execute',
        1 => 'papi\\core\\integrations\\http\\makehttprequest',
        2 => 'papi\\core\\integrations\\http\\formatheaders',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Integrations/Process/ProcessNode.php' => 
    array (
      0 => 'c526f3f76abb00692fdf7fcf012b1487d272eb2c',
      1 => 
      array (
        0 => 'papi\\core\\integrations\\process\\processnode',
      ),
      2 => 
      array (
        0 => 'papi\\core\\integrations\\process\\execute',
        1 => 'papi\\core\\integrations\\process\\processdata',
        2 => 'papi\\core\\integrations\\process\\executeoperation',
        3 => 'papi\\core\\integrations\\process\\getnestedvalue',
        4 => 'papi\\core\\integrations\\process\\evaluateexpression',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Node.php' => 
    array (
      0 => '46f18dffa7b8cf1e0627db38cf9ddf814ddd7d5d',
      1 => 
      array (
        0 => 'papi\\core\\node',
      ),
      2 => 
      array (
        0 => 'papi\\core\\__construct',
        1 => 'papi\\core\\execute',
        2 => 'papi\\core\\validate',
        3 => 'papi\\core\\getinputschema',
        4 => 'papi\\core\\getoutputschema',
        5 => 'papi\\core\\setconfig',
        6 => 'papi\\core\\getconfig',
        7 => 'papi\\core\\getid',
        8 => 'papi\\core\\getname',
        9 => 'papi\\core\\toarray',
      ),
      3 => 
      array (
      ),
    ),
    '/Users/md/code/MarcelloDuarte/Papi/papi-core/src/Workflow.php' => 
    array (
      0 => '046ba48ff187ea19b6273fd7290afd78239a8797',
      1 => 
      array (
        0 => 'papi\\core\\workflow',
      ),
      2 => 
      array (
        0 => 'papi\\core\\__construct',
        1 => 'papi\\core\\addnode',
        2 => 'papi\\core\\addconnection',
        3 => 'papi\\core\\execute',
        4 => 'papi\\core\\validate',
        5 => 'papi\\core\\toarray',
        6 => 'papi\\core\\fromarray',
        7 => 'papi\\core\\getname',
        8 => 'papi\\core\\getnodes',
        9 => 'papi\\core\\getconnections',
      ),
      3 => 
      array (
      ),
    ),
  ),
));