<?php


class DBBuilder
{
	
	private $sql = array("
		CREATE TABLE IF NOT EXISTS `veh_traj` (
		  `id` bigint(11) NOT NULL AUTO_INCREMENT,
		  `EEIS_Enabled` int(2) NOT NULL DEFAULT '0' COMMENT 'Speed advice enabled or disabled',
		  `VehNr` int(11) NOT NULL COMMENT 'Vehicle ID',
		  `LVeh` int(11) NOT NULL COMMENT 'ID of the next vehicle downstream',
		  `Type` int(6) NOT NULL COMMENT 'Vehicle Type ID',
		  `VehTypeName` varchar(20) NOT NULL COMMENT 'Vehicle Type Name',
		  `Length` float DEFAULT '0' COMMENT 'Vehicle Length [m]',
		  `t` float NOT NULL DEFAULT '-1' COMMENT 'Simulation Time [s]',
		  `a` float NOT NULL COMMENT 'Acceleration [m/s^2] during the simulation step',
		  `v` float NOT NULL COMMENT 'Speed [m/s] at the end of the simulation step',
		  `DesLn` int(4) NOT NULL COMMENT 'Desired Lane (by Direction decision)',
		  `Grad` float NOT NULL DEFAULT '0' COMMENT 'Gradient [%] of the current link',
		  `WorldX` float NOT NULL COMMENT 'World coordinate x (vehicle front end at the end of the simulation step)',
		  `WorldY` float NOT NULL COMMENT 'World coordinate y (vehicle front end at the end of the simulation step)',
		  `WorldZ` float NOT NULL COMMENT 'World coordinate z (vehicle front end at the end of the simulation step)',
		  `RWorldX` float NOT NULL COMMENT 'World coordinate x (vehicle rear end at the end of the time step)',
		  `RWorldY` float NOT NULL COMMENT 'World coordinate y (vehicle rear end at the end of the time step)',
		  `RWorldZ` float NOT NULL COMMENT 'World coordinate z (vehicle rear end at the end of the time step)',
		  `x` float NOT NULL COMMENT 'Distance from the start position of the current section or turn the vehicle is in to the front part of the vehicle [m] at the end of the simultion step',
		  `y` float NOT NULL COMMENT 'Lateral position relative to middle of lane (0.5) at the end of the simulation step',
		  `comment` varchar(200) NOT NULL COMMENT 'Comment',
		  PRIMARY KEY (`id`),
		  KEY `VehNrIDX` (`VehNr`),
		  KEY `VehTypenameIDX` (`VehTypeName`),
		  KEY `EEIS_idx` (`EEIS_Enabled`)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;	

		");

	public function run($dbi, $logger)
	{
		$ret = '{"error" : "DBBuilder Failed"}';
		$db = new MySQLI2($dbi["hostname"], $dbi["username"], $dbi["passwd"], $dbi["dbname"]);
		if ($db->status() == "OK")
		{
			foreach($this->sql as $sql)
			{
				$res = $db->query($sql);
				if (isset($res["error"]))
				{
					$logger->log("***DBBuilder: ". $res["error"]."\n");
					$ret = '{"error": "DBBuilder "'.$res["error"].'}';
				}
				else
					$ret = '{"msg": "DBBuilder Succeeded"}';
			}
		}
		else
		{
			$logger->log("***DBBuilder: ". $db->status()."\n");
			$ret = '{"error": "DBBuilder "'.$db->status().'}';
		}
		return $ret;
	}
	
}
