import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'screens/home_screen.dart';
import 'screens/intro_screen.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final prefs = await SharedPreferences.getInstance();
  final bool hasSeenIntro = prefs.getBool('has_seen_intro') ?? false;

  runApp(MaoneArtGudangApp(showIntro: !hasSeenIntro));
}

class MaoneArtGudangApp extends StatelessWidget {
  final bool showIntro;
  const MaoneArtGudangApp({super.key, this.showIntro = false});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'MaoneArt Gudang',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        scaffoldBackgroundColor: const Color(0xFF0F172A),
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF2563EB),
          brightness: Brightness.dark,
          surface: const Color(0xFF1E293B),
        ),
        textTheme: GoogleFonts.plusJakartaSansTextTheme(
          ThemeData(brightness: Brightness.dark).textTheme,
        ),
      ),
      home: showIntro ? const IntroScreen() : const HomeScreen(),
    );
  }
}
