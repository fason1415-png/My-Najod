import 'package:flutter/material.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const NajotLinkApp());
}

class NajotLinkApp extends StatelessWidget {
  const NajotLinkApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'MY NajotLink',
      theme: ThemeData.dark(useMaterial3: true),
      home: const HomeScreen(),
    );
  }
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  String status = 'SOS tugmani bosing';

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('MY NajotLink Mobile')),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 180,
              height: 180,
              decoration: const BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(colors: [Color(0xFFFF9AB1), Color(0xFFFF4D6D), Color(0xFF7A0820)]),
              ),
              child: InkWell(
                onTap: () {
                  setState(() {
                    status = 'SOS yuborish oqimi yoqildi. Bu repoda to\'liq kod web qismida mavjud.';
                  });
                },
                child: const Center(
                  child: Text('SOS', style: TextStyle(fontSize: 48, fontWeight: FontWeight.bold)),
                ),
              ),
            ),
            const SizedBox(height: 24),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 24),
              child: Text(status, textAlign: TextAlign.center),
            )
          ],
        ),
      ),
    );
  }
}
