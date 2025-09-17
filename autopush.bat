@echo off
cd D:\Programs\xampp\htdocs
git add .
git commit -m "Auto commit %date% %time%"
git push origin main
