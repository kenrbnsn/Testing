function mangle5 (strContent, strUsername, strDomain, strType, strSubject)
{
  if (mangle5.arguments.length <5)
    return;

  document.write ("<a href=\"mailto:" + strUsername + "@" + strDomain + ".");

  if (strType == "c")
    document.write ("com");
  else if (strType == "o")
    document.write ("org");
  else if (strType == "e")
    document.write ("edu");
  else if (strType == "n")
    document.write ("net");
  else
    document.write (strType);

  document.write ("?subject=" + strSubject + "\">" + strContent + "</a>");
}
